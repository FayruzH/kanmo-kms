<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\SopComment;
use App\Models\SopCategory;
use App\Models\SopDepartment;
use App\Models\SopDocument;
use App\Models\SopLike;
use App\Services\AiSearchService;
use App\Services\SopActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SopPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $query = SopDocument::query()
            ->with(['category', 'department', 'pic', 'tags'])
            ->withCount([
                'likes',
                'comments',
                'activityLogs as views_count',
            ])
            ->whereIn('status', ['active', 'expiring_soon', 'expired'])
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $search = $request->string('search')->value();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('summary', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        return view('employee.dashboard', [
            'items' => $query->paginate(9)->withQueryString(),
            'totals' => [
                'all' => SopDocument::query()->count(),
                'active' => SopDocument::query()->where('status', 'active')->count(),
                'expiring_soon' => SopDocument::query()->where('status', 'expiring_soon')->count(),
                'expired' => SopDocument::query()->where('status', 'expired')->count(),
            ],
            'categories' => SopCategory::query()->where('active', true)->orderBy('name')->get(),
            'departments' => SopDepartment::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function index(Request $request)
    {
        return view('employee.ai.index');
    }

    public function show(SopDocument $sop, SopActivityService $activityService)
    {
        $sop->load(['category', 'department', 'pic', 'tags', 'comments.user'])
            ->loadCount([
                'likes',
                'comments',
                'activityLogs as views_count',
            ]);
        $activityService->log($sop->id, auth()->id(), 'view', request()->userAgent());

        return view('employee.sop.show', [
            'sop' => $sop,
            'liked' => SopLike::query()
                ->where('sop_id', $sop->id)
                ->where('user_id', auth()->id())
                ->exists(),
        ]);
    }

    public function ask(Request $request, AiSearchService $aiSearchService)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:300'],
        ]);

        $result = $aiSearchService->search($data['q']);

        return view('employee.ai.index', [
            'query' => $data['q'],
            'answer' => $result['answer'],
            'items' => $result['items'],
        ]);
    }

    public function open(SopDocument $sop, SopActivityService $activityService)
    {
        $activityService->log($sop->id, auth()->id(), 'open', request()->userAgent());

        if ($sop->type === 'url' && $sop->url) {
            return redirect()->away($sop->url);
        }

        abort_unless($sop->file_path, 404, 'SOP file not found.');
        $url = Storage::disk('public')->url($sop->file_path);

        return redirect($url);
    }

    public function download(SopDocument $sop, SopActivityService $activityService)
    {
        $activityService->log($sop->id, auth()->id(), 'download', request()->userAgent());

        abort_unless($sop->file_path, 404, 'SOP file not found.');
        return Storage::disk('public')->download($sop->file_path, $sop->title . '.pdf');
    }

    public function like(SopDocument $sop)
    {
        SopLike::query()->firstOrCreate([
            'sop_id' => $sop->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'SOP liked.');
    }

    public function unlike(SopDocument $sop)
    {
        SopLike::query()
            ->where('sop_id', $sop->id)
            ->where('user_id', auth()->id())
            ->delete();

        return back()->with('success', 'Like removed.');
    }

    public function comment(Request $request, SopDocument $sop)
    {
        $data = $request->validate([
            'comment_text' => ['required', 'string', 'max:2000'],
        ]);

        SopComment::query()->create([
            'sop_id' => $sop->id,
            'user_id' => auth()->id(),
            'comment_text' => $data['comment_text'],
        ]);

        return back()->with('success', 'Comment submitted.');
    }
}
