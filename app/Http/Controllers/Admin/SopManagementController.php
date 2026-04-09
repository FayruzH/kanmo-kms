<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSopDocumentRequest;
use App\Http\Requests\UpdateSopDocumentRequest;
use App\Models\SopCategory;
use App\Models\SopDepartment;
use App\Models\SopDocument;
use App\Models\SopSourceApp;
use App\Models\SopTag;
use App\Models\User;
use App\Services\SopStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use ZipArchive;

class SopManagementController extends Controller
{
    private const DASHBOARD_STAT_KEYS = ['all', 'active', 'expiring_soon', 'expired'];
    private const DASHBOARD_STATUS_KEYS = ['active', 'expiring_soon', 'expired', 'archived'];

    public function dashboard(Request $request)
    {
        $q = SopDocument::query()
            ->with(['category', 'department', 'pic', 'tags'])
            ->withCount([
                'likes',
                'comments',
                'activityLogs as views_count',
            ])
            ->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }

        if ($request->filled('department_id')) {
            $q->where('department_id', $request->integer('department_id'));
        }

        if ($request->filled('category_id')) {
            $q->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->value();
            $q->where(function ($inner) use ($search) {
                $inner->where('title', 'like', '%' . $search . '%')
                    ->orWhere('summary', 'like', '%' . $search . '%');
            });
        }

        $items = $q->paginate(9)->withQueryString();
        $totals = [
            'all' => SopDocument::query()->count(),
            'active' => SopDocument::query()->where('status', 'active')->count(),
            'expiring_soon' => SopDocument::query()->where('status', 'expiring_soon')->count(),
            'expired' => SopDocument::query()->where('status', 'expired')->count(),
        ];

        return view('admin.dashboard', [
            'items' => $items,
            'totals' => $totals,
            'categories' => SopCategory::query()->where('active', true)->orderBy('name')->get(),
            'departments' => SopDepartment::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function statDetails(Request $request)
    {
        $validated = $request->validate([
            'stat' => ['required', Rule::in(self::DASHBOARD_STAT_KEYS)],
            'search' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:sop_departments,id'],
            'category_id' => ['nullable', 'integer', 'exists:sop_categories,id'],
            'status' => ['nullable', Rule::in(self::DASHBOARD_STATUS_KEYS)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:50'],
        ]);

        $baseQuery = SopDocument::query();
        $this->applyDashboardStatFilters($baseQuery, $validated);

        $query = (clone $baseQuery)
            ->select([
                'id',
                'title',
                'department_id',
                'category_id',
                'pic_user_id',
                'status',
                'expiry_date',
                'updated_at',
            ])
            ->with([
                'department:id,name',
                'category:id,name',
                'pic:id,name',
            ])
            ->orderByDesc('updated_at');

        $perPage = (int) ($validated['per_page'] ?? 10);
        $rows = $query->paginate($perPage)->withQueryString();

        $divisionSummary = (clone $baseQuery)
            ->leftJoin('sop_categories as summary_category', 'summary_category.id', '=', 'sop_documents.category_id')
            ->selectRaw('summary_category.id as id')
            ->selectRaw("COALESCE(summary_category.name, '-') as label")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('summary_category.id')
            ->groupByRaw("COALESCE(summary_category.name, '-')")
            ->orderByDesc('total')
            ->orderBy('label')
            ->get()
            ->map(static fn ($row): array => [
                'id' => $row->id !== null ? (int) $row->id : null,
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->values();

        $departmentSummary = (clone $baseQuery)
            ->leftJoin('sop_departments as summary_department', 'summary_department.id', '=', 'sop_documents.department_id')
            ->selectRaw('summary_department.id as id')
            ->selectRaw("COALESCE(summary_department.name, '-') as label")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('summary_department.id')
            ->groupByRaw("COALESCE(summary_department.name, '-')")
            ->orderByDesc('total')
            ->orderBy('label')
            ->get()
            ->map(static fn ($row): array => [
                'id' => $row->id !== null ? (int) $row->id : null,
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->values();

        return response()->json([
            'data' => $rows->getCollection()->map(static function (SopDocument $sop): array {
                return [
                    'id' => $sop->id,
                    'sop_code' => 'SOP-' . str_pad((string) $sop->id, 3, '0', STR_PAD_LEFT),
                    'title' => (string) $sop->title,
                    'department' => (string) ($sop->department?->name ?? '-'),
                    'division' => (string) ($sop->category?->name ?? '-'),
                    'pic' => (string) ($sop->pic?->name ?? '-'),
                    'status' => (string) $sop->status,
                    'status_label' => ucfirst(str_replace('_', ' ', (string) $sop->status)),
                    'expiry_date' => optional($sop->expiry_date)->toDateString(),
                    'expiry_date_label' => optional($sop->expiry_date)->format('d M Y'),
                    'updated_at' => optional($sop->updated_at)->toIso8601String(),
                    'updated_at_label' => optional($sop->updated_at)->timezone('Asia/Jakarta')->format('d M Y H:i'),
                    'detail_url' => route('admin.sop.show', $sop),
                ];
            })->values(),
            'summaries' => [
                'by_division' => $divisionSummary,
                'by_department' => $departmentSummary,
            ],
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'from' => $rows->firstItem(),
                'to' => $rows->lastItem(),
            ],
        ]);
    }

    private function applyDashboardStatFilters(Builder $query, array $validated): void
    {
        if (!empty($validated['search'])) {
            $search = trim((string) $validated['search']);
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', '%' . $search . '%')
                    ->orWhere('summary', 'like', '%' . $search . '%');
            });
        }

        if (!empty($validated['department_id'])) {
            $query->where('department_id', (int) $validated['department_id']);
        }

        if (!empty($validated['category_id'])) {
            $query->where('category_id', (int) $validated['category_id']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', (string) $validated['status']);
        }

        $stat = (string) ($validated['stat'] ?? 'all');
        if ($stat !== 'all') {
            $query->where('status', $stat);
        }
    }

    public function index(Request $request)
    {
        $q = SopDocument::query()
            ->with(['category', 'department', 'pic', 'tags'])
            ->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }

        if ($request->filled('department_id')) {
            $q->where('department_id', $request->integer('department_id'));
        }

        if ($request->filled('category_id')) {
            $q->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->value();
            $q->where(function ($inner) use ($search) {
                $inner->where('title', 'like', '%' . $search . '%')
                    ->orWhere('summary', 'like', '%' . $search . '%');
            });
        }

        return view('admin.sop.index', [
            'items' => $q->paginate(15)->withQueryString(),
            'categories' => SopCategory::query()->where('active', true)->orderBy('name')->get(),
            'departments' => SopDepartment::query()->where('active', true)->orderBy('name')->get(),
            'sourceApps' => SopSourceApp::query()->where('active', true)->orderBy('name')->get(),
            'pics' => User::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $q = SopDocument::query()
            ->with(['category', 'department', 'pic'])
            ->orderBy('id');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }

        if ($request->filled('department_id')) {
            $q->where('department_id', $request->integer('department_id'));
        }

        if ($request->filled('category_id')) {
            $q->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->value();
            $q->where(function ($inner) use ($search) {
                $inner->where('title', 'like', '%' . $search . '%')
                    ->orWhere('summary', 'like', '%' . $search . '%');
            });
        }

        $headers = [
            'SOP Code',
            'Title',
            'Department',
            'Division',
            'Type',
            'Status',
            'Version',
            'Effective Date',
            'Expiry Date',
            'PIC',
            'Source Name',
            'Entity',
            'URL',
        ];

        $rows = $q->get()->map(static function (SopDocument $doc): array {
            return [
                'SOP-' . str_pad((string) $doc->id, 3, '0', STR_PAD_LEFT),
                (string) ($doc->title ?? ''),
                (string) ($doc->department?->name ?? ''),
                (string) ($doc->category?->name ?? ''),
                strtoupper((string) ($doc->type ?? '')),
                ucfirst(str_replace('_', ' ', (string) ($doc->status ?? ''))),
                (string) ($doc->version ?? ''),
                (string) (optional($doc->effective_date)->toDateString() ?? ''),
                (string) (optional($doc->expiry_date)->toDateString() ?? ''),
                (string) ($doc->pic?->name ?? ''),
                (string) ($doc->source_name ?? ''),
                (string) ($doc->entity ?? ''),
                (string) ($doc->url ?? ''),
            ];
        })->all();

        $xlsx = $this->buildExportXlsx($headers, $rows);
        $filename = 'sop-management-' . now()->format('Ymd_His') . '.xlsx';

        return response($xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function create()
    {
        return view('admin.sop.create', $this->formOptions());
    }

    public function store(StoreSopDocumentRequest $request, SopStatusService $statusService)
    {
        $data = $request->validated();
        $tagsInput = $data['tags'] ?? null;
        unset($data['tags'], $data['file']);
        $existing = $this->findDocumentByTitle((string) ($data['title'] ?? ''));
        $isUpdate = $existing !== null;

        if (($data['type'] ?? null) === 'file' && $request->hasFile('file')) {
            if ($existing?->file_path) {
                Storage::disk('public')->delete($existing->file_path);
            }

            $uploaded = $request->file('file');
            $data['file_path'] = $uploaded->store('sop', 'public');
            $data['file_mime'] = $uploaded->getMimeType();
            $data['url'] = null;
        }

        if (($data['type'] ?? null) === 'url') {
            if ($existing?->file_path) {
                Storage::disk('public')->delete($existing->file_path);
            }

            $data['file_path'] = null;
            $data['file_mime'] = null;
        }

        if (($data['status'] ?? null) === 'archived') {
            $data['archived_at'] = $existing?->archived_at ?? now();
        } else {
            $data['archived_at'] = null;
        }

        if ($existing) {
            $existing->update($data);
            $doc = $existing;
        } else {
            $doc = SopDocument::create($data);
        }

        if ($doc->status !== 'archived') {
            $doc->status = $statusService->resolveStatus($doc);
            $doc->save();
        }
        $this->syncTags($doc, $tagsInput);

        return redirect()->route('admin.sop.index')->with('success', $isUpdate ? 'SOP updated (duplicate title detected).' : 'SOP created.');
    }

    public function edit(SopDocument $sop)
    {
        $sop->load('tags');

        return view('admin.sop.edit', array_merge($this->formOptions(), [
            'sop' => $sop,
            'tagsText' => $sop->tags->pluck('name')->implode(', '),
        ]));
    }

    public function show(SopDocument $sop)
    {
        $sop->load(['category', 'department', 'pic', 'tags', 'comments.user'])
            ->loadCount([
                'likes',
                'comments',
                'activityLogs as views_count',
            ]);

        return view('admin.sop.show', [
            'sop' => $sop,
        ]);
    }

    public function update(UpdateSopDocumentRequest $request, SopDocument $sop, SopStatusService $statusService)
    {
        $data = $request->validated();
        $tagsInput = $data['tags'] ?? null;
        unset($data['tags'], $data['file']);

        if (($data['type'] ?? null) === 'file' && $request->hasFile('file')) {
            if ($sop->file_path) {
                Storage::disk('public')->delete($sop->file_path);
            }

            $uploaded = $request->file('file');
            $data['file_path'] = $uploaded->store('sop', 'public');
            $data['file_mime'] = $uploaded->getMimeType();
            $data['url'] = null;
        }

        if (($data['type'] ?? null) === 'url') {
            if ($sop->file_path) {
                Storage::disk('public')->delete($sop->file_path);
            }

            $data['file_path'] = null;
            $data['file_mime'] = null;
        }

        if (($data['status'] ?? null) === 'archived') {
            $data['archived_at'] = $sop->archived_at ?? now();
        } else {
            $data['archived_at'] = null;
        }

        $sop->update($data);
        if ($sop->status !== 'archived') {
            $sop->status = $statusService->resolveStatus($sop);
            $sop->save();
        }
        $this->syncTags($sop, $tagsInput);

        return redirect()->route('admin.sop.index')->with('success', 'SOP updated.');
    }

    public function destroy(SopDocument $sop)
    {
        if ($sop->file_path) {
            Storage::disk('public')->delete($sop->file_path);
        }

        $sop->tags()->detach();
        $sop->delete();

        return redirect()->route('admin.sop.index')->with('success', 'SOP deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'sop_ids' => ['required', 'array', 'min:1'],
            'sop_ids.*' => ['required', 'integer', 'exists:sop_documents,id'],
        ]);

        $ids = collect($data['sop_ids'])
            ->map(static fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $sops = SopDocument::query()->whereIn('id', $ids)->get();
        foreach ($sops as $sop) {
            if ($sop->file_path) {
                Storage::disk('public')->delete($sop->file_path);
            }

            $sop->tags()->detach();
            $sop->delete();
        }

        return redirect()->route('admin.sop.index')
            ->with('success', count($ids).' SOP deleted.');
    }

    private function syncTags(SopDocument $document, ?string $tagsInput): void
    {
        if ($tagsInput === null) {
            return;
        }

        $tagNames = collect(explode(',', $tagsInput))
            ->map(static fn($tag) => trim($tag))
            ->filter()
            ->unique();

        $tagIds = $tagNames
            ->map(static fn($name) => SopTag::firstOrCreate(['name' => $name])->id)
            ->all();

        $document->tags()->sync($tagIds);
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

    private function buildExportXlsx(array $headers, array $rows): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'kms_sop_export_');
        if ($tempPath === false) {
            abort(500, 'Failed to create temporary export file.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::OVERWRITE) !== true) {
            @unlink($tempPath);
            abort(500, 'Failed to build export workbook.');
        }

        $generatedAt = gmdate('Y-m-d\TH:i:s\Z');
        $sheetXml = $this->buildExportSheetXml($headers, $rows);

        $zip->addFromString('[Content_Types].xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
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
        <sheet name="SOP Management" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>
XML);

        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
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
        <fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill>
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
        $zip->close();

        $content = file_get_contents($tempPath);
        @unlink($tempPath);
        if ($content === false) {
            abort(500, 'Failed to read generated export workbook.');
        }

        return $content;
    }

    private function buildExportSheetXml(array $headers, array $rows): string
    {
        $columnWidths = [12, 38, 22, 22, 10, 16, 12, 14, 14, 24, 24, 18, 44];
        $colsXml = '';
        foreach ($headers as $index => $header) {
            $width = $columnWidths[$index] ?? 18;
            $columnNumber = $index + 1;
            $colsXml .= '<col min="' . $columnNumber . '" max="' . $columnNumber . '" width="' . $width . '" customWidth="1"/>';
        }

        $sheetDataXml = '<row r="1" ht="24" customHeight="1">';
        foreach ($headers as $index => $header) {
            $cellRef = $this->xlsxCellRef($index + 1, 1);
            $sheetDataXml .= '<c r="' . $cellRef . '" s="1" t="inlineStr"><is><t>' . $this->escapeXml((string) $header) . '</t></is></c>';
        }
        $sheetDataXml .= '</row>';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;
            $sheetDataXml .= '<row r="' . $excelRow . '" ht="21" customHeight="1">';
            foreach ($headers as $index => $header) {
                $value = (string) ($row[$index] ?? '');
                $cellRef = $this->xlsxCellRef($index + 1, $excelRow);
                $sheetDataXml .= '<c r="' . $cellRef . '" s="2" t="inlineStr"><is><t>' . $this->escapeXml($value) . '</t></is></c>';
            }
            $sheetDataXml .= '</row>';
        }

        $lastColumn = $this->xlsxColumnName(count($headers));
        $lastRow = count($rows) + 1;
        $range = 'A1:' . $lastColumn . $lastRow;

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
</worksheet>
XML;
    }

    private function xlsxCellRef(int $column, int $row): string
    {
        return $this->xlsxColumnName($column) . $row;
    }

    private function xlsxColumnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $mod = ($column - 1) % 26;
            $name = chr(65 + $mod) . $name;
            $column = (int) floor(($column - 1) / 26);
        }

        return $name;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function formOptions(): array
    {
        return [
            'categories' => SopCategory::query()->where('active', true)->orderBy('name')->get(),
            'departments' => SopDepartment::query()->where('active', true)->orderBy('name')->get(),
            'sourceApps' => SopSourceApp::query()->where('active', true)->orderBy('name')->get(),
            'pics' => User::query()->where('active', true)->orderBy('name')->get(),
        ];
    }
}
