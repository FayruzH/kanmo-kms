<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\SopComment;
use App\Models\SopCategory;
use App\Models\SopDepartment;
use App\Models\SopDocument;
use App\Models\SopLike;
use App\Services\SopActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SopPortalController extends Controller
{
    private const DASHBOARD_STAT_KEYS = ['all', 'active', 'expiring_soon', 'expired'];
    private const DASHBOARD_STATUS_KEYS = ['active', 'expiring_soon', 'expired'];

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

        $query = SopDocument::query()
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
            ->whereIn('status', self::DASHBOARD_STATUS_KEYS)
            ->orderByDesc('updated_at');

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

        $stat = (string) $validated['stat'];
        if ($stat !== 'all') {
            $query->where('status', $stat);
        }

        $perPage = (int) ($validated['per_page'] ?? 10);
        $rows = $query->paginate($perPage)->withQueryString();

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
                    'detail_url' => route('employee.sop.show', $sop),
                ];
            })->values(),
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
