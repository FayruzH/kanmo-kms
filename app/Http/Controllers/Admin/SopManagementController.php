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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SopManagementController extends Controller
{
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

    public function create()
    {
        return view('admin.sop.create', $this->formOptions());
    }

    public function store(StoreSopDocumentRequest $request, SopStatusService $statusService)
    {
        $data = $request->validated();
        $tagsInput = $data['tags'] ?? null;
        unset($data['tags'], $data['file']);

        if (($data['type'] ?? null) === 'file' && $request->hasFile('file')) {
            $uploaded = $request->file('file');
            $data['file_path'] = $uploaded->store('sop', 'public');
            $data['file_mime'] = $uploaded->getMimeType();
            $data['url'] = null;
        }

        if (($data['status'] ?? null) === 'archived') {
            $data['archived_at'] = now();
        }

        $doc = SopDocument::create($data);
        if ($doc->status !== 'archived') {
            $doc->status = $statusService->resolveStatus($doc);
            $doc->save();
        }
        $this->syncTags($doc, $tagsInput);

        return redirect()->route('admin.sop.index')->with('success', 'SOP created.');
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
