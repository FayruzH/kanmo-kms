<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\SopCategory;
use App\Models\SopDepartment;
use App\Models\SopDocument;
use App\Models\SopSourceApp;
use App\Models\SopTag;
use App\Models\User;
use App\Services\SopStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class SopImportController extends Controller
{
    public function index()
    {
        $batches = ImportBatch::query()
            ->with('admin')
            ->withCount([
                'rows as processed_rows_count',
                'rows as success_rows_count' => fn ($q) => $q->where('status', 'success'),
                'rows as failed_rows_count' => fn ($q) => $q->where('status', 'failed'),
            ])
            ->latest()
            ->paginate(10);

        $batchIds = $batches->getCollection()
            ->pluck('id')
            ->all();

        $failedRowsByBatch = ImportBatchRow::query()
            ->whereIn('batch_id', $batchIds)
            ->where('status', 'failed')
            ->orderBy('row_number')
            ->get(['batch_id', 'row_number', 'error_message', 'raw_json'])
            ->groupBy('batch_id');

        return view('admin.sop.import', [
            'batches' => $batches,
            'failedRowsByBatch' => $failedRowsByBatch,
        ]);
    }

    public function template()
    {
        $headers = [
            'title',
            'division',
            'department',
            'entity',
            'source_name',
            'type',
            'url',
            'version',
            'effective_date',
            'expiry_date',
            'pic_nip',
            'summary',
            'tags',
        ];

        $rows = [
            [
                'Store Opening Checklist',
                'Operations',
                'Retail',
                'Kanmo Group',
                'SharePoint',
                'url',
                'https://example.com/sop/store-opening',
                'v1.0',
                '2026-01-01',
                '2026-12-31',
                '21619',
                'Checklist SOP pembukaan toko baru.',
                'opening,store,operations',
            ],
            [
                'Employee Onboarding',
                'Human Resource',
                'Talent Acquisition',
                'Kanmo Group',
                'Internal Portal',
                'url',
                'https://example.com/sop/onboarding',
                'v2.1',
                '2026-02-15',
                '2027-02-14',
                '16299',
                'Alur onboarding end-to-end karyawan baru.',
                'onboarding,hr,new-hire',
            ],
        ];

        $dropdownDefinitions = [
            [
                'name' => 'DivisionOptions',
                'title' => 'Division',
                'values' => $this->normalizeOptionValues(
                    SopCategory::query()->where('active', true)->orderBy('name')->pluck('name')->all(),
                    ['General']
                ),
                'target' => 'B2:B1000',
            ],
            [
                'name' => 'DepartmentOptions',
                'title' => 'Department',
                'values' => $this->normalizeOptionValues(
                    SopDepartment::query()->where('active', true)->orderBy('name')->pluck('name')->all(),
                    ['General']
                ),
                'target' => 'C2:C1000',
            ],
            [
                'name' => 'EntityOptions',
                'title' => 'Entity',
                'values' => $this->normalizeOptionValues(
                    SopDocument::query()
                        ->select('entity')
                        ->whereNotNull('entity')
                        ->where('entity', '!=', '')
                        ->distinct()
                        ->orderBy('entity')
                        ->pluck('entity')
                        ->all(),
                    ['Kanmo Group']
                ),
                'target' => 'D2:D1000',
            ],
            [
                'name' => 'SourceOptions',
                'title' => 'Source Name',
                'values' => $this->normalizeOptionValues(
                    SopSourceApp::query()->where('active', true)->orderBy('name')->pluck('name')->all(),
                    ['SharePoint']
                ),
                'target' => 'E2:E1000',
            ],
            [
                'name' => 'TypeOptions',
                'title' => 'Type',
                'values' => ['url', 'file'],
                'target' => 'F2:F1000',
            ],
        ];

        $xlsx = $this->buildTemplateXlsx($headers, $rows, $dropdownDefinitions);

        return response($xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="kms-sop-template.xlsx"',
        ]);
    }

    public function store(Request $request, SopStatusService $statusService)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $uploadedFile = $request->file('file');
        $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());

        $batch = ImportBatch::query()->create([
            'admin_user_id' => auth()->id(),
            'filename' => $uploadedFile->getClientOriginalName(),
            'totals_json' => ['total' => 0, 'success' => 0, 'failed' => 0],
        ]);

        $total = 0;
        $success = 0;
        $failed = 0;

        $rows = $this->readRows($uploadedFile->getRealPath(), $extension);
        if (empty($rows)) {
            return redirect()->route('admin.sop.import.index')
                ->withErrors(['file' => 'File kosong atau tidak terbaca.']);
        }

        $header = array_map(static fn($col) => trim((string) $col), (array) array_shift($rows));
        $header = $this->normalizeHeaderDuplicates($header);

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if ($this->isRowEmpty($row)) {
                    continue;
                }
                $total++;
                $raw = $this->safeCombine($header, $row);
                $result = $this->importRow($raw, $statusService);

                ImportBatchRow::query()->create([
                    'batch_id' => $batch->id,
                    'row_number' => $total + 1,
                    'status' => $result['status'],
                    'error_message' => $result['error'],
                    'raw_json' => $raw,
                ]);

                if ($result['status'] === 'success') {
                    $success++;
                } else {
                    $failed++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $batch->update([
            'totals_json' => [
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
            ],
        ]);

        if ($failed > 0 && $success === 0) {
            return redirect()->route('admin.sop.import.index')
                ->with('error', "Import gagal. Total: {$total}, Success: {$success}, Failed: {$failed}. Cek detail baris error di Import History.");
        }

        if ($failed > 0) {
            return redirect()->route('admin.sop.import.index')
                ->with('warning', "Import selesai sebagian. Success: {$success}, Failed: {$failed}. Cek detail baris error di Import History.");
        }

        return redirect()->route('admin.sop.import.index')
            ->with('success', "Import selesai. Success: {$success}, Failed: {$failed}");
    }

    private function readRows(string $path, string $extension): array
    {
        if (in_array($extension, ['csv', 'txt'], true)) {
            $rows = [];
            $handle = fopen($path, 'r');
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);

            return $rows;
        }

        if ($extension === 'xlsx') {
            return $this->readXlsxRows($path);
        }

        return [];
    }

    private function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sx = simplexml_load_string($sharedXml);
            if ($sx) {
                foreach ($sx->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } else {
                        $text = '';
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                        $sharedStrings[] = $text;
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);
        if (!$sheet || !isset($sheet->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $rowNode) {
            $row = [];
            foreach ($rowNode->c as $cell) {
                $ref = (string) $cell['r'];
                $column = preg_replace('/\d+/', '', $ref);
                $index = $this->columnIndex($column);

                $type = (string) $cell['t'];
                if ($type === 's') {
                    $sharedIndex = (int) ($cell->v ?? 0);
                    $value = $sharedStrings[$sharedIndex] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                } else {
                    $value = (string) ($cell->v ?? '');
                }

                $row[$index] = trim($value);
            }

            if (!empty($row)) {
                ksort($row);
                $maxIndex = max(array_keys($row));
                $normalized = [];
                for ($i = 0; $i <= $maxIndex; $i++) {
                    $normalized[] = $row[$i] ?? '';
                }
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    private function columnIndex(string $column): int
    {
        $column = strtoupper($column);
        $index = 0;
        for ($i = 0; $i < strlen($column); $i++) {
            $index = ($index * 26) + (ord($column[$i]) - ord('A') + 1);
        }

        return max(0, $index - 1);
    }

    private function safeCombine(array $header, array $row): array
    {
        $headerCount = count($header);
        $row = array_slice(array_pad($row, $headerCount, ''), 0, $headerCount);

        $combined = array_combine($header, $row);
        return is_array($combined) ? $combined : [];
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeHeaderDuplicates(array $header): array
    {
        $seen = [];
        $normalized = [];
        foreach ($header as $name) {
            $key = trim((string) $name);
            if ($key === '') {
                $key = 'col';
            }

            if (!isset($seen[$key])) {
                $seen[$key] = 0;
                $normalized[] = $key;
                continue;
            }

            $seen[$key]++;
            $normalized[] = $key . '_' . $seen[$key];
        }

        return $normalized;
    }

    private function normalizePayload(array $raw): array
    {
        $title = $this->pick($raw, ['title', 'judul', 'sop title']);
        if ($title === null || trim($title) === '') {
            $subs = [
                $this->pick($raw, ['sub 3']),
                $this->pick($raw, ['sub 2']),
                $this->pick($raw, ['sub 1']),
                $this->pick($raw, ['sub 0']),
            ];

            foreach ($subs as $sub) {
                if (!empty(trim((string) $sub))) {
                    $title = $sub;
                    break;
                }
            }
        }

        $category = $this->pick($raw, ['division', 'divisi', 'category', 'kategori', 'sub 0']) ?: 'General';
        $department = $this->pick($raw, ['department', 'departemen', 'sub 1']) ?: 'General';
        $entity = $this->pick($raw, ['entity', 'entitas', 'sub 2']);
        $sourceName = $this->pick($raw, ['source_name', 'source app', 'source']);
        $url = $this->pick($raw, ['url', 'link']);
        $type = $this->pick($raw, ['type', 'jenis']) ?: 'url';
        $version = $this->pick($raw, ['version', 'versi']) ?: 'v1.0';
        $effectiveDate = $this->normalizeDate($this->pick($raw, ['effective_date', 'tgl efektif', 'time stamp']));
        $expiryDate = $this->normalizeDate($this->pick($raw, ['expiry_date', 'expired', 'expiry date', 'tgl', 'tanggal', 'time stamp'])) ?: now()->addYear()->toDateString();
        $picNip = $this->pick($raw, ['pic_nip', 'pic nip', 'nip pic', 'nip_pic', 'owner_nip']);
        $summary = $this->pick($raw, ['summary', 'description', 'remarks']);
        $tags = $this->pick($raw, ['tags', 'tag']);

        if (!empty($sourceName) && filter_var((string) $sourceName, FILTER_VALIDATE_URL)) {
            if (empty($url)) {
                $url = $sourceName;
            }
            $sourceName = 'SharePoint';
        }

        return [
            'title' => trim((string) $title),
            'category' => trim((string) $category),
            'department' => trim((string) $department),
            'entity' => $entity ? trim((string) $entity) : null,
            'source_name' => $sourceName ? mb_substr(trim((string) $sourceName), 0, 255) : null,
            'type' => in_array(strtolower((string) $type), ['url', 'file'], true) ? strtolower((string) $type) : 'url',
            'url' => $url ? trim((string) $url) : null,
            'version' => trim((string) $version),
            'effective_date' => $effectiveDate,
            'expiry_date' => $expiryDate,
            'pic_nip' => $picNip ? trim((string) $picNip) : null,
            'summary' => $summary ? trim((string) $summary) : null,
            'tags' => $tags ? trim((string) $tags) : null,
        ];
    }

    private function pick(array $raw, array $aliases): ?string
    {
        $normalized = [];
        foreach ($raw as $key => $value) {
            $normalized[$this->normalizeKey((string) $key)] = is_string($value) ? trim($value) : (string) $value;
        }

        foreach ($aliases as $alias) {
            $found = $normalized[$this->normalizeKey($alias)] ?? null;
            if ($found !== null && $found !== '') {
                return $found;
            }
        }

        return null;
    }

    private function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        return preg_replace('/[^a-z0-9]+/', ' ', $key) ?: '';
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $text = trim((string) $value);
        if (is_numeric($text)) {
            $serial = (float) $text;
            $base = \Carbon\Carbon::create(1899, 12, 30, 0, 0, 0, 'UTC');
            return $base->addDays((int) floor($serial))->toDateString();
        }

        try {
            return \Carbon\Carbon::parse($text)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function importRow(array $raw, SopStatusService $statusService): array
    {
        $data = $this->normalizePayload($raw);

        $validator = Validator::make($data, [
            'title' => ['required', 'string'],
            'category' => ['required', 'string'],
            'department' => ['required', 'string'],
            'expiry_date' => ['required', 'date'],
            'pic_nip' => ['required', 'string', 'max:10', 'regex:/^\d+$/'],
            'type' => ['required', 'in:url,file'],
            'tags' => ['nullable', 'string'],
        ], [], [
            'category' => 'division',
            'pic_nip' => 'PIC NIP',
        ]);

        if ($validator->fails()) {
            return ['status' => 'failed', 'error' => implode('; ', $validator->errors()->all())];
        }

        $sourceText = strtolower(trim((string) ($data['source_name'] ?? '')));
        if (str_contains($sourceText, 'bukan sop')) {
            return ['status' => 'failed', 'error' => 'Skipped: row marked as Bukan SOP'];
        }

        $category = SopCategory::query()->firstOrCreate(
            ['name' => trim((string) $data['category'])],
            ['active' => true]
        );
        $department = SopDepartment::query()->firstOrCreate(
            ['name' => trim((string) $data['department'])],
            ['active' => true]
        );
        $sourceAppId = null;
        if (!empty($data['source_name']) && mb_strlen((string) $data['source_name']) <= 100) {
            $sourceApp = SopSourceApp::query()->firstOrCreate(
                ['name' => trim((string) $data['source_name'])],
                ['active' => true]
            );
            $sourceAppId = $sourceApp->id;
        }

        $pic = User::query()->where('nip', User::normalizeNip(trim((string) $data['pic_nip'])))->first();
        if (!$pic) {
            return ['status' => 'failed', 'error' => 'PIC user tidak ditemukan untuk NIP: '.$data['pic_nip']];
        }

        $attributes = [
            'title' => $data['title'],
            'category_id' => $category->id,
            'department_id' => $department->id,
            'entity' => $data['entity'],
            'source_app_id' => $sourceAppId,
            'source_name' => $data['source_name'],
            'type' => $data['type'],
            'url' => $data['url'],
            'version' => $data['version'],
            'effective_date' => $data['effective_date'] ?: null,
            'expiry_date' => $data['expiry_date'],
            'pic_user_id' => $pic->id,
            'summary' => $data['summary'],
            'status' => 'active',
            'archived_at' => null,
        ];

        if ($data['type'] === 'url') {
            $attributes['file_path'] = null;
            $attributes['file_mime'] = null;
        }

        $doc = $this->findDocumentByTitle($data['title']);
        if ($doc) {
            $doc->fill($attributes);
            $doc->save();
        } else {
            $doc = SopDocument::query()->create($attributes);
        }

        $doc->status = $statusService->resolveStatus($doc);
        $doc->save();
        $this->syncTags($doc, $data['tags'] ?? null);

        return ['status' => 'success', 'error' => null];
    }

    private function findDocumentByTitle(string $title): ?SopDocument
    {
        $normalizedTitle = mb_strtolower(trim($title));
        if ($normalizedTitle === '') {
            return null;
        }

        return SopDocument::query()
            ->whereRaw('LOWER(TRIM(title)) = ?', [$normalizedTitle])
            ->orderByDesc('id')
            ->first();
    }

    private function syncTags(SopDocument $document, ?string $tagsInput): void
    {
        if ($tagsInput === null) {
            return;
        }

        $tagIds = collect(explode(',', $tagsInput))
            ->map(static fn($tag) => trim($tag))
            ->filter()
            ->unique()
            ->map(static fn($name) => SopTag::query()->firstOrCreate(['name' => $name])->id)
            ->values()
            ->all();

        $document->tags()->sync($tagIds);
    }

    private function normalizeOptionValues(array $values, array $fallback): array
    {
        $normalized = collect($values)
            ->map(static fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return empty($normalized) ? $fallback : $normalized;
    }

    private function buildTemplateXlsx(array $headers, array $rows, array $dropdownDefinitions): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'kms_tpl_');
        if ($tempPath === false) {
            abort(500, 'Failed to create temporary template file.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::OVERWRITE) !== true) {
            @unlink($tempPath);
            abort(500, 'Failed to build template workbook.');
        }

        $listColumns = [];
        $namedRanges = [];
        $validations = [];
        foreach ($dropdownDefinitions as $index => $definition) {
            $name = (string) ($definition['name'] ?? '');
            $target = (string) ($definition['target'] ?? '');
            if ($name === '' || $target === '') {
                continue;
            }

            $values = $this->normalizeOptionValues((array) ($definition['values'] ?? []), ['-']);
            $columnIndex = $index + 1;
            $columnName = $this->xlsxColumnName($columnIndex);

            $listColumns[] = [
                'column' => $columnIndex,
                'title' => (string) ($definition['title'] ?? $name),
                'values' => $values,
            ];
            $namedRanges[] = [
                'name' => $name,
                'ref' => 'Lists!$'.$columnName.'$2:$'.$columnName.'$'.(1 + count($values)),
            ];
            $validations[] = [
                'target' => $target,
                'name' => $name,
            ];
        }

        $sheetXml = $this->buildTemplateSheetXml($headers, $rows, $validations);
        $listSheetXml = $this->buildListsSheetXml($listColumns);
        $generatedAt = gmdate('Y-m-d\TH:i:s\Z');
        $definedNamesXml = '';
        foreach ($namedRanges as $namedRange) {
            $definedNamesXml .= '<definedName name="'.$this->escapeXml((string) $namedRange['name']).'">'.
                $this->escapeXml((string) $namedRange['ref']).
                '</definedName>';
        }
        $definedNamesNode = $definedNamesXml !== '' ? '<definedNames>'.$definedNamesXml.'</definedNames>' : '';

        $zip->addFromString('[Content_Types].xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML);

        $zip->addFromString('_rels/.rels', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML);

        $zip->addFromString('docProps/app.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
    <Application>Kanmo KMS</Application>
</Properties>
XML);

        $zip->addFromString('docProps/core.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:creator>Kanmo KMS</dc:creator>
    <cp:lastModifiedBy>Kanmo KMS</cp:lastModifiedBy>
    <dcterms:created xsi:type="dcterms:W3CDTF">{$generatedAt}</dcterms:created>
    <dcterms:modified xsi:type="dcterms:W3CDTF">{$generatedAt}</dcterms:modified>
</cp:coreProperties>
XML);

        $zip->addFromString('xl/workbook.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Template" sheetId="1" r:id="rId1"/>
        <sheet name="Lists" sheetId="2" state="hidden" r:id="rId2"/>
    </sheets>
    {$definedNamesNode}
</workbook>
XML);

        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML);

        $zip->addFromString('xl/styles.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/><scheme val="minor"/></font>
        <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/><scheme val="minor"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFF26A21"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color rgb="FFBFC7D5"/></left>
            <right style="thin"><color rgb="FFBFC7D5"/></right>
            <top style="thin"><color rgb="FFBFC7D5"/></top>
            <bottom style="thin"><color rgb="FFBFC7D5"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="3">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center" wrapText="1"/>
        </xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">
            <alignment vertical="top" wrapText="1"/>
        </xf>
    </cellXfs>
    <cellStyles count="1">
        <cellStyle name="Normal" xfId="0" builtinId="0"/>
    </cellStyles>
    <dxfs count="0"/>
    <tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>
XML);

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->addFromString('xl/worksheets/sheet2.xml', $listSheetXml);
        $zip->close();

        $content = file_get_contents($tempPath);
        @unlink($tempPath);

        if ($content === false) {
            abort(500, 'Failed to read generated template workbook.');
        }

        return $content;
    }

    private function buildTemplateSheetXml(array $headers, array $rows, array $validations): string
    {
        $columnWidths = [30, 20, 20, 18, 18, 10, 42, 10, 14, 14, 14, 40, 28];
        $colsXml = '';
        foreach ($headers as $index => $header) {
            $width = $columnWidths[$index] ?? 18;
            $colNum = $index + 1;
            $colsXml .= '<col min="'.$colNum.'" max="'.$colNum.'" width="'.$width.'" customWidth="1"/>';
        }

        $sheetDataXml = '<row r="1" ht="26" customHeight="1">';
        foreach ($headers as $index => $header) {
            $cellRef = $this->xlsxCellRef($index + 1, 1);
            $sheetDataXml .= '<c r="'.$cellRef.'" s="1" t="inlineStr"><is><t>'.$this->escapeXml($header).'</t></is></c>';
        }
        $sheetDataXml .= '</row>';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;
            $sheetDataXml .= '<row r="'.$excelRow.'" ht="22" customHeight="1">';
            foreach ($headers as $index => $header) {
                $value = (string) ($row[$index] ?? '');
                $cellRef = $this->xlsxCellRef($index + 1, $excelRow);
                $sheetDataXml .= '<c r="'.$cellRef.'" s="2" t="inlineStr"><is><t>'.$this->escapeXml($value).'</t></is></c>';
            }
            $sheetDataXml .= '</row>';
        }

        $lastColumn = $this->xlsxColumnName(count($headers));
        $lastRow = count($rows) + 1;
        $range = 'A1:'.$lastColumn.$lastRow;
        $validationXml = '';
        $validationCount = 0;
        foreach ($validations as $validation) {
            $target = trim((string) ($validation['target'] ?? ''));
            $name = trim((string) ($validation['name'] ?? ''));
            if ($target === '' || $name === '') {
                continue;
            }

            $validationCount++;
            $validationXml .= '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="'.
                $this->escapeXml($target).
                '"><formula1>='.$this->escapeXml($name).'</formula1></dataValidation>';
        }
        $validationNode = $validationCount > 0
            ? '<dataValidations count="'.$validationCount.'">'.$validationXml.'</dataValidations>'
            : '';

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <dimension ref="{$range}"/>
    <sheetViews>
        <sheetView workbookViewId="0">
            <pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>
        </sheetView>
    </sheetViews>
    <sheetFormatPr defaultRowHeight="15"/>
    <cols>{$colsXml}</cols>
    <sheetData>{$sheetDataXml}</sheetData>
    <autoFilter ref="{$range}"/>
    {$validationNode}
</worksheet>
XML;
    }

    private function buildListsSheetXml(array $listColumns): string
    {
        $columnCount = max(1, count($listColumns));
        $maxRows = 1;
        $colsXml = '';
        foreach ($listColumns as $column) {
            $columnIndex = (int) $column['column'];
            $colsXml .= '<col min="'.$columnIndex.'" max="'.$columnIndex.'" width="28" customWidth="1"/>';
            $maxRows = max($maxRows, 1 + count((array) ($column['values'] ?? [])));
        }
        if ($colsXml === '') {
            $colsXml = '<col min="1" max="1" width="28" customWidth="1"/>';
        }

        $sheetDataXml = '<row r="1" ht="22" customHeight="1">';
        foreach ($listColumns as $column) {
            $columnIndex = (int) $column['column'];
            $cellRef = $this->xlsxCellRef($columnIndex, 1);
            $title = (string) ($column['title'] ?? 'Options');
            $sheetDataXml .= '<c r="'.$cellRef.'" s="1" t="inlineStr"><is><t>'.$this->escapeXml($title).'</t></is></c>';
        }
        if (empty($listColumns)) {
            $sheetDataXml .= '<c r="A1" s="1" t="inlineStr"><is><t>Options</t></is></c>';
        }
        $sheetDataXml .= '</row>';

        for ($rowNumber = 2; $rowNumber <= $maxRows; $rowNumber++) {
            $sheetDataXml .= '<row r="'.$rowNumber.'">';
            foreach ($listColumns as $column) {
                $columnIndex = (int) $column['column'];
                $values = (array) ($column['values'] ?? []);
                $value = (string) ($values[$rowNumber - 2] ?? '');
                $cellRef = $this->xlsxCellRef($columnIndex, $rowNumber);
                $sheetDataXml .= '<c r="'.$cellRef.'" t="inlineStr"><is><t>'.$this->escapeXml($value).'</t></is></c>';
            }
            if (empty($listColumns)) {
                $cellRef = $this->xlsxCellRef(1, $rowNumber);
                $sheetDataXml .= '<c r="'.$cellRef.'" t="inlineStr"><is><t></t></is></c>';
            }
            $sheetDataXml .= '</row>';
        }

        $lastColumn = $this->xlsxColumnName($columnCount);
        $dimension = 'A1:'.$lastColumn.$maxRows;

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <dimension ref="{$dimension}"/>
    <sheetViews>
        <sheetView workbookViewId="0"/>
    </sheetViews>
    <sheetFormatPr defaultRowHeight="15"/>
    <cols>{$colsXml}</cols>
    <sheetData>{$sheetDataXml}</sheetData>
</worksheet>
XML;
    }

    private function xlsxCellRef(int $column, int $row): string
    {
        return $this->xlsxColumnName($column).$row;
    }

    private function xlsxColumnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $mod = ($column - 1) % 26;
            $name = chr(65 + $mod).$name;
            $column = (int) floor(($column - 1) / 26);
        }

        return $name;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
