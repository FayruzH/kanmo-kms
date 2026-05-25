<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\SopComment;
use App\Models\SopCategory;
use App\Models\SopDepartment;
use App\Models\SopDocument;
use App\Models\SopLike;
use App\Models\User;
use App\Services\SopActivityService;
use App\Services\SopSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SopPortalController extends Controller
{
    private const DASHBOARD_STAT_KEYS = ['all', 'active', 'expiring_soon', 'expired'];
    private const DASHBOARD_STATUS_KEYS = ['active', 'expiring_soon', 'expired'];

    public function dashboard(Request $request, SopSearchService $searchService)
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

        $searchContext = $searchService->applyToQuery($query, $request->input('search'));

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        $items = $query->paginate(9)->withQueryString();
        $searchService->appendSnippets($items->getCollection(), $searchContext);

        return view('employee.dashboard', [
            'items' => $items,
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

    public function statDetails(Request $request, SopSearchService $searchService)
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

        $searchContext = $searchService->buildContext($validated['search'] ?? null);

        $baseQuery = SopDocument::query()->whereIn('status', self::DASHBOARD_STATUS_KEYS);

        if (!empty($validated['department_id'])) {
            $baseQuery->where('department_id', (int) $validated['department_id']);
        }

        if (!empty($validated['category_id'])) {
            $baseQuery->where('category_id', (int) $validated['category_id']);
        }

        if (!empty($validated['status'])) {
            $baseQuery->where('status', (string) $validated['status']);
        }

        $stat = (string) $validated['stat'];
        if ($stat !== 'all') {
            $baseQuery->where('status', $stat);
        }

        $searchService->applyFilters($baseQuery, $searchContext);

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
        $searchService->applyRanking($query, $searchContext);

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
                    'detail_url' => route('employee.sop.show', $sop),
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

    public function show(Request $request, SopDocument $sop, SopActivityService $activityService)
    {
        $sop->load(['category', 'department', 'pic', 'tags', 'comments.user'])
            ->loadCount([
                'likes',
                'comments',
                'activityLogs as views_count',
            ]);
        $userId = auth()->id();
        if ($userId) {
            $activityService->log($sop->id, $userId, 'view', request()->userAgent());
        }

        $viewer = $this->resolveInteractionUser($request, false);

        return view('employee.sop.show', [
            'sop' => $sop,
            'liked' => $viewer
                ? SopLike::query()
                    ->where('sop_id', $sop->id)
                    ->where('user_id', $viewer->id)
                    ->exists()
                : false,
        ]);
    }

    public function open(SopDocument $sop, SopActivityService $activityService)
    {
        $userId = auth()->id();
        if ($userId) {
            $activityService->log($sop->id, $userId, 'open', request()->userAgent());
        }

        if ($sop->type === 'url' && $sop->url) {
            return redirect()->away($sop->url);
        }

        abort_unless($sop->file_path, 404, 'SOP file not found.');
        $url = Storage::disk('public')->url($sop->file_path);

        return redirect($url);
    }

    public function download(SopDocument $sop, SopActivityService $activityService)
    {
        $userId = auth()->id();
        if ($userId) {
            $activityService->log($sop->id, $userId, 'download', request()->userAgent());
        }

        abort_unless($sop->file_path, 404, 'SOP file not found.');
        return Storage::disk('public')->download($sop->file_path, $sop->title . '.pdf');
    }

    public function like(Request $request, SopDocument $sop)
    {
        $actor = $this->resolveInteractionUser($request);

        SopLike::query()->firstOrCreate([
            'sop_id' => $sop->id,
            'user_id' => $actor->id,
        ]);

        return back()->with('success', 'SOP liked.');
    }

    public function unlike(Request $request, SopDocument $sop)
    {
        $actor = $this->resolveInteractionUser($request, false);
        if (!$actor) {
            return back()->with('warning', 'No like found for this visitor session.');
        }

        SopLike::query()
            ->where('sop_id', $sop->id)
            ->where('user_id', $actor->id)
            ->delete();

        return back()->with('success', 'Like removed.');
    }

    public function comment(Request $request, SopDocument $sop)
    {
        $data = $request->validate([
            'comment_text' => ['required', 'string', 'max:2000'],
            'guest_name' => ['nullable', 'string', 'max:80'],
        ]);

        $actor = $this->resolveInteractionUser($request, true, $data['guest_name'] ?? null);

        SopComment::query()->create([
            'sop_id' => $sop->id,
            'user_id' => $actor->id,
            'comment_text' => $data['comment_text'],
        ]);

        return back()->with('success', 'Comment submitted.');
    }

    private function resolveInteractionUser(Request $request, bool $createIfMissing = true, ?string $guestName = null): ?User
    {
        if ($request->user()) {
            return $request->user();
        }

        $sessionKey = 'public_interaction_user_id';
        $sessionUserId = $request->session()->get($sessionKey);
        if ($sessionUserId) {
            $user = User::query()->find((int) $sessionUserId);
            if ($user) {
                if ($guestName) {
                    $user->name = trim($guestName);
                    $user->save();
                }

                return $user;
            }
        }

        if (!$createIfMissing) {
            return null;
        }

        $guestDisplayName = trim((string) ($guestName ?? 'Public Guest'));
        if ($guestDisplayName === '') {
            $guestDisplayName = 'Public Guest';
        }

        $guestId = (string) Str::uuid();
        $user = User::query()->create([
            'name' => Str::limit($guestDisplayName, 80, ''),
            'email' => 'public-guest-' . $guestId . '@kms.local',
            'nip' => $this->generateGuestNip(),
            'password' => Hash::make(Str::random(32)),
            'role' => 'employee',
            'active' => false,
        ]);

        $request->session()->put($sessionKey, $user->id);

        return $user;
    }

    private function generateGuestNip(): string
    {
        do {
            $candidate = (string) random_int(1000000000, 9999999999);
        } while (User::query()->where('nip', $candidate)->exists());

        return $candidate;
    }
}
