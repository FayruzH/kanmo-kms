<?php

namespace App\Services;

use App\Models\SopDocument;
use Illuminate\Support\Collection;

class AiSearchService
{
    public function search(string $query, int $limit = 5): array
    {
        $items = SopDocument::query()
            ->with(['category', 'department', 'tags'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                    ->orWhere('summary', 'like', '%' . $query . '%');
            })
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        $answer = $this->buildGroundedAnswer($query, $items);

        return [
            'answer' => $answer,
            'items' => $items,
        ];
    }

    private function buildGroundedAnswer(string $query, Collection $items): string
    {
        if ($items->isEmpty()) {
            return 'Belum ada SOP yang cukup relevan untuk pertanyaan ini. Coba kata kunci yang lebih spesifik.';
        }

        $lines = [];
        foreach ($items->take(3) as $index => $item) {
            $summary = trim((string) $item->summary);
            $summary = $summary !== '' ? mb_substr($summary, 0, 140) : 'Tidak ada ringkasan.';
            $citation = '[' . ($index + 1) . '] ' . $item->title;
            $lines[] = $citation . ': ' . $summary;
        }

        return 'Hasil terbaik untuk "' . $query . "\" berdasarkan metadata SOP:\n- " . implode("\n- ", $lines);
    }
}
