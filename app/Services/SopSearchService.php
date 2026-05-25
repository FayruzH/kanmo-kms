<?php

namespace App\Services;

use App\Models\SopDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SopSearchService
{
    public function buildContext(?string $rawQuery): ?array
    {
        $raw = trim((string) $rawQuery);
        if ($raw === '') {
            return null;
        }

        $normalized = $this->normalize($raw);
        if ($normalized === '') {
            return null;
        }

        $tokens = collect(preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY))
            ->filter(static fn (string $token): bool => mb_strlen($token) >= 2)
            ->reject(fn (string $token): bool => in_array($token, $this->stopWords(), true))
            ->values();

        $terms = collect([$normalized])
            ->merge($tokens)
            ->merge($this->expandByConcepts($normalized, $tokens->all()))
            ->merge($this->expandByTokenMap($tokens->all()))
            ->map(fn (string $term): string => $this->normalize($term))
            ->filter()
            ->unique()
            ->sortByDesc(static fn (string $term): int => mb_strlen($term))
            ->values()
            ->take($this->maxTerms())
            ->values();

        if ($terms->isEmpty()) {
            return null;
        }

        return [
            'raw_query' => $raw,
            'normalized_query' => $normalized,
            'terms' => $terms->all(),
            'fulltext_query' => $terms->take(6)->implode(' '),
        ];
    }

    public function applyToQuery(Builder $query, ?string $rawQuery, bool $rankResults = true): ?array
    {
        $context = $this->buildContext($rawQuery);
        if ($context === null) {
            return null;
        }

        $this->applyFilters($query, $context);
        if ($rankResults) {
            $this->applyRanking($query, $context);
        }

        return $context;
    }

    public function applyFilters(Builder $query, ?array $context): void
    {
        if ($context === null) {
            return;
        }

        $terms = (array) ($context['terms'] ?? []);
        if ($terms === []) {
            return;
        }

        $fulltextQuery = (string) ($context['fulltext_query'] ?? '');

        $query->where(function (Builder $inner) use ($terms, $fulltextQuery): void {
            foreach ($terms as $term) {
                $like = '%' . $term . '%';

                $inner->orWhereRaw('LOWER(sop_documents.title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(sop_documents.summary, "")) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(sop_documents.source_name, "")) LIKE ?', [$like])
                    ->orWhereHas('tags', static function (Builder $tagQuery) use ($like): void {
                        $tagQuery->whereRaw('LOWER(sop_tags.name) LIKE ?', [$like]);
                    })
                    ->orWhereHas('category', static function (Builder $categoryQuery) use ($like): void {
                        $categoryQuery->whereRaw('LOWER(sop_categories.name) LIKE ?', [$like]);
                    })
                    ->orWhereHas('department', static function (Builder $departmentQuery) use ($like): void {
                        $departmentQuery->whereRaw('LOWER(sop_departments.name) LIKE ?', [$like]);
                    })
                    ->orWhereHas('sourceApp', static function (Builder $sourceAppQuery) use ($like): void {
                        $sourceAppQuery->whereRaw('LOWER(sop_source_apps.name) LIKE ?', [$like]);
                    });
            }

            if ($fulltextQuery !== '') {
                $inner->orWhereRaw(
                    'MATCH(sop_documents.title, sop_documents.summary) AGAINST (? IN NATURAL LANGUAGE MODE)',
                    [$fulltextQuery]
                );
            }
        });
    }

    public function applyRanking(Builder $query, ?array $context): void
    {
        if ($context === null) {
            return;
        }

        [$scoreSql, $scoreBindings] = $this->buildScoreExpression($context);

        $query->selectRaw('(' . $scoreSql . ') AS search_score', $scoreBindings);
        $query->reorder()
            ->orderByDesc('search_score')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }

    public function appendSnippets(Collection $documents, ?array $context): void
    {
        if ($context === null || $documents->isEmpty()) {
            return;
        }

        $terms = (array) ($context['terms'] ?? []);
        if ($terms === []) {
            return;
        }

        $documents->each(function (SopDocument $document) use ($terms): void {
            $snippet = $this->buildSnippet($document, $terms);
            if ($snippet !== null) {
                $document->setAttribute('search_snippet', $snippet);
            }
        });
    }

    private function buildScoreExpression(array $context): array
    {
        $normalizedQuery = (string) ($context['normalized_query'] ?? '');
        $terms = (array) ($context['terms'] ?? []);
        $fulltextQuery = (string) ($context['fulltext_query'] ?? '');

        $parts = [];
        $bindings = [];

        $parts[] = 'CASE WHEN LOWER(sop_documents.title) = ? THEN ' . $this->weight('title_exact', 180) . ' ELSE 0 END';
        $bindings[] = $normalizedQuery;

        $parts[] = 'CASE WHEN LOWER(sop_documents.title) LIKE ? THEN ' . $this->weight('title_prefix', 120) . ' ELSE 0 END';
        $bindings[] = $normalizedQuery . '%';

        $parts[] = 'CASE WHEN LOWER(sop_documents.title) LIKE ? THEN ' . $this->weight('title_phrase', 90) . ' ELSE 0 END';
        $bindings[] = '%' . $normalizedQuery . '%';

        foreach ($terms as $term) {
            $like = '%' . $term . '%';

            $parts[] = 'CASE WHEN LOWER(sop_documents.title) LIKE ? THEN ' . $this->weight('title_term', 45) . ' ELSE 0 END';
            $bindings[] = $like;

            $parts[] = 'CASE WHEN LOWER(COALESCE(sop_documents.summary, "")) LIKE ? THEN ' . $this->weight('summary_term', 28) . ' ELSE 0 END';
            $bindings[] = $like;

            $parts[] = 'CASE WHEN LOWER(COALESCE(sop_documents.source_name, "")) LIKE ? THEN ' . $this->weight('source_name_term', 16) . ' ELSE 0 END';
            $bindings[] = $like;

            $parts[] = 'CASE WHEN EXISTS (
                SELECT 1
                FROM sop_document_tag sdt
                INNER JOIN sop_tags st ON st.id = sdt.sop_tag_id
                WHERE sdt.sop_document_id = sop_documents.id
                  AND LOWER(st.name) LIKE ?
            ) THEN ' . $this->weight('tag_term', 36) . ' ELSE 0 END';
            $bindings[] = $like;

            $parts[] = 'CASE WHEN EXISTS (
                SELECT 1
                FROM sop_categories sc
                WHERE sc.id = sop_documents.category_id
                  AND LOWER(sc.name) LIKE ?
            ) THEN ' . $this->weight('category_term', 18) . ' ELSE 0 END';
            $bindings[] = $like;

            $parts[] = 'CASE WHEN EXISTS (
                SELECT 1
                FROM sop_departments sd
                WHERE sd.id = sop_documents.department_id
                  AND LOWER(sd.name) LIKE ?
            ) THEN ' . $this->weight('department_term', 18) . ' ELSE 0 END';
            $bindings[] = $like;

            $parts[] = 'CASE WHEN EXISTS (
                SELECT 1
                FROM sop_source_apps ssa
                WHERE ssa.id = sop_documents.source_app_id
                  AND LOWER(ssa.name) LIKE ?
            ) THEN ' . $this->weight('source_app_term', 14) . ' ELSE 0 END';
            $bindings[] = $like;
        }

        if ($fulltextQuery !== '') {
            $parts[] = 'COALESCE(MATCH(sop_documents.title, sop_documents.summary) AGAINST (? IN NATURAL LANGUAGE MODE), 0) * '
                . $this->weight('fulltext_multiplier', 24);
            $bindings[] = $fulltextQuery;
        }

        return [implode(' + ', $parts), $bindings];
    }

    private function buildSnippet(SopDocument $document, array $terms): ?string
    {
        $summary = trim((string) ($document->summary ?? ''));
        if ($summary !== '') {
            $snippet = $this->makeExcerpt($summary, $terms);
            if ($snippet !== null) {
                return $snippet;
            }
        }

        $title = trim((string) ($document->title ?? ''));
        if ($title !== '') {
            $snippet = $this->makeExcerpt($title, $terms);
            if ($snippet !== null) {
                return 'Title: ' . $snippet;
            }
        }

        if ($document->relationLoaded('tags')) {
            $tags = $document->tags->pluck('name')->filter()->implode(', ');
            if ($tags !== '' && $this->containsAnyTerm($tags, $terms)) {
                return 'Tags: ' . Str::limit($tags, $this->snippetLength());
            }
        }

        if ($document->relationLoaded('category')) {
            $category = trim((string) ($document->category?->name ?? ''));
            if ($category !== '' && $this->containsAnyTerm($category, $terms)) {
                return 'Division: ' . $category;
            }
        }

        if ($document->relationLoaded('department')) {
            $department = trim((string) ($document->department?->name ?? ''));
            if ($department !== '' && $this->containsAnyTerm($department, $terms)) {
                return 'Department: ' . $department;
            }
        }

        $source = trim((string) ($document->source_name ?? ''));
        if ($source !== '' && $this->containsAnyTerm($source, $terms)) {
            return 'Source: ' . Str::limit($source, $this->snippetLength());
        }

        if ($document->relationLoaded('sourceApp')) {
            $sourceAppName = trim((string) ($document->sourceApp?->name ?? ''));
            if ($sourceAppName !== '' && $this->containsAnyTerm($sourceAppName, $terms)) {
                return 'Source App: ' . $sourceAppName;
            }
        }

        return null;
    }

    private function makeExcerpt(string $text, array $terms): ?string
    {
        $lowerText = Str::lower($text);
        $length = $this->snippetLength();

        foreach ($terms as $term) {
            $term = trim((string) $term);
            if ($term === '') {
                continue;
            }

            $position = mb_stripos($lowerText, $term);
            if ($position === false) {
                continue;
            }

            $start = max(0, $position - 36);
            $snippet = trim(mb_substr($text, $start, $length));
            if ($snippet === '') {
                continue;
            }

            if ($start > 0) {
                $snippet = '...' . $snippet;
            }

            if (mb_strlen($text) > ($start + $length)) {
                $snippet .= '...';
            }

            return $snippet;
        }

        return null;
    }

    private function containsAnyTerm(string $text, array $terms): bool
    {
        $normalizedText = $this->normalize($text);
        if ($normalizedText === '') {
            return false;
        }

        foreach ($terms as $term) {
            $term = $this->normalize((string) $term);
            if ($term !== '' && str_contains($normalizedText, $term)) {
                return true;
            }
        }

        return false;
    }

    private function expandByConcepts(string $normalizedQuery, array $tokens): Collection
    {
        $queryWithPadding = ' ' . $normalizedQuery . ' ';

        return collect(config('sop_search.concepts', []))
            ->filter(static fn ($concept): bool => is_array($concept) && $concept !== [])
            ->flatMap(function (array $concept) use ($queryWithPadding, $tokens): Collection {
                $variants = collect($concept)
                    ->map(fn (string $variant): string => $this->normalize($variant))
                    ->filter()
                    ->unique()
                    ->values();

                $isMatched = $variants->contains(static function (string $variant) use ($queryWithPadding, $tokens): bool {
                    return str_contains($queryWithPadding, ' ' . $variant . ' ')
                        || in_array($variant, $tokens, true);
                });

                return $isMatched ? $variants : collect();
            })
            ->values();
    }

    private function expandByTokenMap(array $tokens): Collection
    {
        $tokenMap = (array) config('sop_search.token_map', []);

        return collect($tokens)
            ->flatMap(static function (string $token) use ($tokenMap): Collection {
                $mappedTerms = (array) ($tokenMap[$token] ?? []);

                return collect($mappedTerms)
                    ->filter()
                    ->values();
            })
            ->values();
    }

    private function normalize(string $value): string
    {
        $ascii = Str::ascii(Str::lower($value));
        $cleaned = preg_replace('/[^a-z0-9\s]/', ' ', $ascii) ?? '';
        $collapsed = preg_replace('/\s+/', ' ', trim($cleaned)) ?? '';

        return trim($collapsed);
    }

    private function weight(string $key, float $default): float
    {
        return (float) data_get(config('sop_search.weights', []), $key, $default);
    }

    private function maxTerms(): int
    {
        return max(1, (int) config('sop_search.max_terms', 12));
    }

    private function snippetLength(): int
    {
        return max(60, (int) config('sop_search.snippet_length', 140));
    }

    private function stopWords(): array
    {
        return collect((array) config('sop_search.stop_words', []))
            ->map(fn (string $word): string => $this->normalize($word))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
