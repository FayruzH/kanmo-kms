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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SopManagementController extends Controller
{
    public function index(Request $request)
    {
        $q = SopDocument::query()
            ->with(['category','department','pic'])
            ->orderByDesc('updated_at');

        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('department_id')) $q->where('department_id', $request->department_id);
        if ($request->filled('category_id')) $q->where('category_id', $request->category_id);

        if ($request->filled('search')) {
            $q->whereFullText(['title','summary'], $request->search);
        }

        return view('admin.sop.index', [
            'items' => $q->paginate(15)->withQueryString(),
            'categories' => SopCategory::where('active', true)->orderBy('name')->get(),
            'departments' => SopDepartment::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.sop.create', [
            'categories' => SopCategory::where('active', true)->orderBy('name')->get(),
            'departments' => SopDepartment::where('active', true)->orderBy('name')->get(),
            'sourceApps' => SopSourceApp::where('active', true)->orderBy('name')->get(),
            'pics' => User::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreSopDocumentRequest $request)
    {
        $data = $request->validated();
        $tagsInput = $data['tags'] ?? null;
        unset($data['tags'], $data['file']);

        $filePath = null;
        $fileMime = null;

        if (($data['type'] ?? null) === 'file' && $request->hasFile('file')) {
            $filePath = $request->file('file')->store('sop', 'public');
            $fileMime = $request->file('file')->getMimeType();
            $data['url'] = null;
        }

        $doc = SopDocument::create([
            ...$data,
            'file_path' => $filePath,
            'file_mime' => $fileMime,
        ]);

        // tags: "a, b, c"
        if (!empty($tagsInput)) {
            $tagNames = collect(explode(',', $tagsInput))
                ->map(fn($t) => trim($t))
                ->filter()
                ->unique();

            $tagIds = $tagNames->map(function ($name) {
                return SopTag::firstOrCreate(['name' => $name])->id;
            })->all();

            $doc->tags()->sync($tagIds);
        }

        return redirect()->route('admin.sop.index')->with('success', 'SOP created.');
    }

    public function edit(SopDocument $sop)
    {
        $sop->load('tags');

        return view('admin.sop.edit', [
            'sop' => $sop,
            'categories' => SopCategory::where('active', true)->orderBy('name')->get(),
            'departments' => SopDepartment::where('active', true)->orderBy('name')->get(),
            'sourceApps' => SopSourceApp::where('active', true)->orderBy('name')->get(),
            'pics' => User::where('active', true)->orderBy('name')->get(),
            'tagsText' => $sop->tags->pluck('name')->implode(', '),
        ]);
    }

    public function update(UpdateSopDocumentRequest $request, SopDocument $sop)
    {
        $data = $request->validated();
        $tagsInput = $data['tags'] ?? null;
        unset($data['tags'], $data['file']);

        if (($data['type'] ?? null) === 'file' && $request->hasFile('file')) {
            if ($sop->file_path) Storage::disk('public')->delete($sop->file_path);
            $data['file_path'] = $request->file('file')->store('sop', 'public');
            $data['file_mime'] = $request->file('file')->getMimeType();
            $data['url'] = null;
        }

        $sop->update($data);

        if ($tagsInput !== null) {
            $tagNames = collect(explode(',', $tagsInput))
                ->map(fn($t) => trim($t))
                ->filter()
                ->unique();

            $tagIds = $tagNames->map(fn($name) => SopTag::firstOrCreate(['name' => $name])->id)->all();

            $sop->tags()->sync($tagIds);
        }

        return redirect()->route('admin.sop.index')->with('success', 'SOP updated.');
    }
}
