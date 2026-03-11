<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\SopCategory;
use App\Models\SopDepartment;
use App\Models\SopDocument;
use App\Models\SopSourceApp;
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
        $batches = ImportBatch::query()->with('admin')->latest()->paginate(10);

        return view('admin.sop.import', [
            'batches' => $batches,
        ]);
    }

    public function template()
    {
        $headers = [
            'title',
            'category',
            'department',
            'entity',
            'source_name',
            'type',
            'url',
            'version',
            'effective_date',
            'expiry_date',
            'pic_email',
            'summary',
            'tags',
        ];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);
        fputcsv($handle, ['Store Opening Checklist', 'Operations', 'Retail', '', 'SharePoint', 'url', 'https://example.com/sop', 'v1.0', '2026-01-01', '2026-12-31', 'pic@example.com', 'Checklist SOP', 'opening,store']);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="kms-sop-template.csv"',
        ]);
    }

    public function store(Request $request, SopStatusService $statusService)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
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

        if ($extension === 'xls') {
            return redirect()->route('admin.sop.import.index')
                ->withErrors(['file' => 'Format .xls belum didukung. Simpan file sebagai .xlsx atau .csv terlebih dahulu.']);
        }

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

        $category = $this->pick($raw, ['category', 'kategori', 'sub 0']) ?: 'General';
        $department = $this->pick($raw, ['department', 'departemen', 'sub 1']) ?: 'General';
        $entity = $this->pick($raw, ['entity', 'entitas', 'sub 2']);
        $sourceName = $this->pick($raw, ['source_name', 'source app', 'source']);
        $url = $this->pick($raw, ['url', 'link']);
        $type = $this->pick($raw, ['type', 'jenis']) ?: 'url';
        $version = $this->pick($raw, ['version', 'versi']) ?: 'v1.0';
        $effectiveDate = $this->normalizeDate($this->pick($raw, ['effective_date', 'tgl efektif', 'time stamp']));
        $expiryDate = $this->normalizeDate($this->pick($raw, ['expiry_date', 'expired', 'expiry date', 'tgl', 'tanggal', 'time stamp'])) ?: now()->addYear()->toDateString();
        $picEmail = $this->pick($raw, ['pic_email', 'pic email', 'email pic']);
        $picName = $this->pick($raw, ['pic', 'pic_name', 'pic user', 'owner']);
        $summary = $this->pick($raw, ['summary', 'description', 'remarks']);

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
            'pic_email' => $picEmail ? trim((string) $picEmail) : null,
            'pic_name' => $picName ? trim((string) $picName) : null,
            'summary' => $summary ? trim((string) $summary) : null,
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
            'pic_email' => ['nullable', 'email'],
            'pic_name' => ['nullable', 'string'],
            'type' => ['required', 'in:url,file'],
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

        $pic = null;
        if (!empty($data['pic_email'])) {
            $pic = User::query()->where('email', trim((string) $data['pic_email']))->first();
        }
        if (!$pic && !empty($data['pic_name'])) {
            $pic = User::query()->where('name', trim((string) $data['pic_name']))->first();
        }
        if (!$pic) {
            $pic = auth()->user();
        }

        if (!$pic) {
            return ['status' => 'failed', 'error' => 'PIC user tidak ditemukan dan fallback admin tidak tersedia.'];
        }

        $doc = SopDocument::query()->create([
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
            'status' => 'active',
            'summary' => $data['summary'],
        ]);

        $doc->status = $statusService->resolveStatus($doc);
        $doc->save();

        return ['status' => 'success', 'error' => null];
    }
}
