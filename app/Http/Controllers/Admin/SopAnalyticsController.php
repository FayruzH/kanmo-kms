<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SopActivityLog;
use App\Models\SopComment;
use App\Models\SopDocument;
use App\Models\SopLike;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SopAnalyticsController extends Controller
{
    public function index()
    {
        $totals = [
            'all' => SopDocument::query()->count(),
            'views' => SopActivityLog::query()->whereIn('event_type', ['view', 'open', 'download'])->count(),
            'likes' => SopLike::query()->count(),
            'comments' => SopComment::query()->count(),
        ];

        $topViewed = SopActivityLog::query()
            ->select('sop_id', DB::raw('count(*) as total_views'))
            ->whereIn('event_type', ['view', 'open', 'download'])
            ->groupBy('sop_id')
            ->orderByDesc('total_views')
            ->with('sop')
            ->limit(5)
            ->get();

        $topLiked = SopLike::query()
            ->select('sop_id', DB::raw('count(*) as total_likes'))
            ->groupBy('sop_id')
            ->orderByDesc('total_likes')
            ->with('sop')
            ->limit(5)
            ->get();

        $months = collect(range(5, 0))
            ->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));

        $viewsByMonthRaw = SopActivityLog::query()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, COUNT(*) as total')
            ->whereIn('event_type', ['view', 'open', 'download'])
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $commentsByMonthRaw = SopComment::query()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, COUNT(*) as total')
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $viewsTrendLabels = $months
            ->map(fn (Carbon $month) => $month->format('M'))
            ->values()
            ->all();

        $viewsTrendData = $months
            ->map(fn (Carbon $month) => (int) ($viewsByMonthRaw[$month->format('Y-m')] ?? 0))
            ->values()
            ->all();

        $commentsTrendData = $months
            ->map(fn (Carbon $month) => (int) ($commentsByMonthRaw[$month->format('Y-m')] ?? 0))
            ->values()
            ->all();

        $viewsByDepartmentRaw = SopActivityLog::query()
            ->join('sop_documents', 'sop_activity_logs.sop_id', '=', 'sop_documents.id')
            ->leftJoin('sop_departments', 'sop_documents.department_id', '=', 'sop_departments.id')
            ->whereIn('sop_activity_logs.event_type', ['view', 'open', 'download'])
            ->selectRaw('COALESCE(sop_departments.name, "Unknown") as department_name, COUNT(*) as total')
            ->groupBy('department_name')
            ->orderByDesc('total')
            ->limit(7)
            ->get();

        $departmentLabels = $viewsByDepartmentRaw->pluck('department_name')->all();
        $departmentData = $viewsByDepartmentRaw->pluck('total')->map(fn ($v) => (int) $v)->all();

        $topViewedLabels = $topViewed
            ->map(fn ($row) => Str::limit($row->sop?->title ?: 'Unknown', 34))
            ->values()
            ->all();
        $topViewedData = $topViewed
            ->map(fn ($row) => (int) ($row->total_views ?? 0))
            ->values()
            ->all();

        $topLikedLabels = $topLiked
            ->map(fn ($row) => Str::limit($row->sop?->title ?: 'Unknown', 34))
            ->values()
            ->all();
        $topLikedData = $topLiked
            ->map(fn ($row) => (int) ($row->total_likes ?? 0))
            ->values()
            ->all();

        return view('admin.analytics.index', compact(
            'totals',
            'topViewed',
            'topLiked',
            'viewsTrendLabels',
            'viewsTrendData',
            'commentsTrendData',
            'departmentLabels',
            'departmentData',
            'topViewedLabels',
            'topViewedData',
            'topLikedLabels',
            'topLikedData'
        ));
    }

    public function export()
    {
        $rows = SopDocument::query()
            ->with(['category', 'department', 'pic'])
            ->withCount([
                'activityLogs as views_count',
                'likes as likes_count',
                'comments as comments_count',
            ])
            ->orderBy('title')
            ->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['title', 'division', 'department', 'pic', 'expiry_date', 'status', 'views', 'likes', 'comments']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->title,
                $row->category?->name,
                $row->department?->name,
                $row->pic?->name,
                optional($row->expiry_date)->format('Y-m-d'),
                $row->status,
                $row->views_count,
                $row->likes_count,
                $row->comments_count,
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sop-analytics.csv"',
        ]);
    }
}
