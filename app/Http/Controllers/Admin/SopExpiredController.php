<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SopGroupedReminderMail;
use App\Mail\SopReminderMail;
use App\Models\ReminderJob;
use App\Models\SopCategory;
use App\Models\SopDepartment;
use App\Models\SopDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SopExpiredController extends Controller
{
    private const REMINDER_STATUSES = ['expired', 'expiring_soon'];
    private const PER_PAGE_OPTIONS = [20, 50, 100, 500, 1000];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(self::REMINDER_STATUSES)],
            'department_id' => ['nullable', 'integer', 'exists:sop_departments,id'],
            'category_id' => ['nullable', 'integer', 'exists:sop_categories,id'],
            'pic_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ]);

        $perPage = (int) ($filters['per_page'] ?? self::PER_PAGE_OPTIONS[0]);

        $scopeQuery = SopDocument::query()->whereIn('status', self::REMINDER_STATUSES);
        $this->applyScopeFilters($scopeQuery, $filters);

        $items = (clone $scopeQuery)
            ->with(['category', 'department', 'pic'])
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $picGroups = (clone $scopeQuery)
            ->select('pic_user_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_total")
            ->selectRaw("SUM(CASE WHEN status = 'expiring_soon' THEN 1 ELSE 0 END) as expiring_total")
            ->groupBy('pic_user_id')
            ->with('pic:id,name,email')
            ->orderByDesc('total')
            ->get();

        return view('admin.sop.expired', [
            'items' => $items,
            'picGroups' => $picGroups,
            'categories' => SopCategory::query()->where('active', true)->orderBy('name')->get(),
            'departments' => SopDepartment::query()->where('active', true)->orderBy('name')->get(),
            'pics' => User::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function remind(SopDocument $sop)
    {
        $result = $this->sendReminderForSop($sop, 'manual');

        if ($result['status'] === 'sent') {
            return back()->with('success', 'Reminder email sent.');
        }

        return back()->with('error', $result['message'] ?? 'Reminder failed to send. Please check mail configuration.');
    }

    public function remindByPic(Request $request)
    {
        $validated = $request->validate([
            'pic_user_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(self::REMINDER_STATUSES)],
            'department_id' => ['nullable', 'integer', 'exists:sop_departments,id'],
            'category_id' => ['nullable', 'integer', 'exists:sop_categories,id'],
        ]);

        $query = SopDocument::query()->whereIn('status', self::REMINDER_STATUSES);
        $this->applyScopeFilters($query, $validated);
        $query->where('pic_user_id', (int) $validated['pic_user_id']);

        $sops = $query
            ->with(['pic', 'category', 'department'])
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->get();

        if ($sops->isEmpty()) {
            return back()->with('warning', 'No expired/expiring SOP found for this PIC.');
        }

        $result = $this->sendGroupedReminderForPic($sops, 'manual_pic');
        if ($result['status'] === 'sent') {
            $picName = $result['pic_name'] ?? 'PIC';
            $sopCount = (int) ($result['sop_count'] ?? 0);

            return back()->with('success', "Grouped reminder sent to {$picName} for {$sopCount} SOP(s).");
        }

        return back()->with('error', $result['message'] ?? 'Grouped reminder failed to send. Please check mail configuration.');
    }

    public function bulkRemind(Request $request)
    {
        $validated = $request->validate([
            'sop_ids' => ['nullable', 'array', 'min:1'],
            'sop_ids.*' => ['required_with:sop_ids', 'integer', 'distinct', 'exists:sop_documents,id'],
            'pic_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(self::REMINDER_STATUSES)],
            'department_id' => ['nullable', 'integer', 'exists:sop_departments,id'],
            'category_id' => ['nullable', 'integer', 'exists:sop_categories,id'],
        ]);

        $hasSopIds = !empty($validated['sop_ids']);
        $hasPic = !empty($validated['pic_user_id']);

        if (!$hasSopIds && !$hasPic) {
            return back()->with('warning', 'Select SOP(s) or choose a PIC first.');
        }

        if ($hasPic && !$hasSopIds) {
            $query = SopDocument::query()->whereIn('status', self::REMINDER_STATUSES);
            $this->applyScopeFilters($query, $validated);
            $query->where('pic_user_id', (int) $validated['pic_user_id']);

            $sops = $query
                ->with(['pic', 'category', 'department'])
                ->orderBy('expiry_date')
                ->orderBy('id')
                ->get();

            if ($sops->isEmpty()) {
                return back()->with('warning', 'No expired/expiring SOP found for this PIC.');
            }

            $result = $this->sendGroupedReminderForPic($sops, 'bulk_pic');
            if ($result['status'] === 'sent') {
                $picName = $result['pic_name'] ?? 'PIC';
                $sopCount = (int) ($result['sop_count'] ?? 0);

                return back()->with('success', "Grouped reminder sent to {$picName} for {$sopCount} SOP(s).");
            }

            return back()->with('error', $result['message'] ?? 'Grouped reminder failed to send. Please check mail configuration.');
        }

        $ids = collect($validated['sop_ids'])
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $sops = SopDocument::query()
            ->with(['pic', 'category', 'department'])
            ->whereIn('status', self::REMINDER_STATUSES)
            ->whereIn('id', $ids)
            ->get();

        $sentPicCount = 0;
        $failedPicCount = 0;
        $sentSopCount = 0;
        $failedSopCount = 0;
        $skippedSopCount = 0;

        $sopsByPic = $sops->groupBy(static fn (SopDocument $sop) => (string) ($sop->pic_user_id ?? ''));

        foreach ($sopsByPic as $picKey => $picSops) {
            if ($picKey === '') {
                $skippedSopCount += $picSops->count();
                continue;
            }

            $result = $this->sendGroupedReminderForPic($picSops, 'bulk');
            if ($result['status'] === 'sent') {
                $sentPicCount++;
                $sentSopCount += (int) ($result['sop_count'] ?? $picSops->count());
            } else {
                $failedPicCount++;
                $failedSopCount += $picSops->count();
            }
        }

        if ($sentPicCount > 0 && $failedPicCount === 0 && $skippedSopCount === 0) {
            return back()->with('success', "Grouped reminder sent to {$sentPicCount} PIC(s) for {$sentSopCount} SOP(s).");
        }

        if ($sentPicCount === 0 && $failedPicCount > 0) {
            return back()->with('error', "Grouped reminder failed for {$failedSopCount} SOP(s). Please check PIC email or mail configuration.");
        }

        $parts = [];
        if ($sentPicCount > 0) {
            $parts[] = "sent to {$sentPicCount} PIC(s) / {$sentSopCount} SOP(s)";
        }
        if ($failedPicCount > 0) {
            $parts[] = "failed for {$failedPicCount} PIC(s) / {$failedSopCount} SOP(s)";
        }
        if ($skippedSopCount > 0) {
            $parts[] = "skipped: {$skippedSopCount} SOP(s) (missing PIC)";
        }

        return back()->with('warning', 'Grouped reminder completed partially (' . implode(', ', $parts) . ').');
    }

    public function archive(SopDocument $sop)
    {
        $sop->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        return back()->with('success', 'SOP archived.');
    }

    public function export(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(self::REMINDER_STATUSES)],
            'department_id' => ['nullable', 'integer', 'exists:sop_departments,id'],
            'category_id' => ['nullable', 'integer', 'exists:sop_categories,id'],
            'pic_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $query = SopDocument::query()->whereIn('status', self::REMINDER_STATUSES);
        $this->applyScopeFilters($query, $filters);

        $rows = $query
            ->with(['category', 'department', 'pic'])
            ->orderBy('expiry_date')
            ->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['title', 'division', 'department', 'pic', 'expiry_date', 'status']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->title,
                $row->category?->name,
                $row->department?->name,
                $row->pic?->name,
                optional($row->expiry_date)->format('Y-m-d'),
                $row->status,
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="expired-sop.csv"',
        ]);
    }

    private function applyScopeFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }
        if (!empty($filters['pic_user_id'])) {
            $query->where('pic_user_id', (int) $filters['pic_user_id']);
        }
    }

    private function sendReminderForSop(SopDocument $sop, string $trigger): array
    {
        $sop->loadMissing('pic');

        if (!$sop->pic_user_id) {
            return [
                'status' => 'failed',
                'message' => 'Reminder failed: PIC user is missing.',
            ];
        }

        $job = ReminderJob::query()->create([
            'sop_id' => $sop->id,
            'pic_user_id' => $sop->pic_user_id,
            'reminder_type' => $sop->status === 'expired' ? 'expired' : 'expiring',
            'status' => 'pending',
            'meta_json' => ['trigger' => $trigger],
        ]);

        if (!$sop->pic || !$sop->pic->email) {
            $job->update([
                'status' => 'failed',
                'meta_json' => array_merge($job->meta_json ?? [], ['error' => 'PIC email is missing.']),
            ]);

            return [
                'status' => 'failed',
                'message' => 'Reminder failed: PIC email is missing.',
            ];
        }

        try {
            Mail::to($sop->pic->email)->send(new SopReminderMail($sop, $job->reminder_type));
            $job->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return [
                'status' => 'sent',
                'message' => 'Reminder email sent.',
            ];
        } catch (\Throwable $e) {
            $job->update([
                'status' => 'failed',
                'meta_json' => array_merge($job->meta_json ?? [], ['error' => $e->getMessage()]),
            ]);

            return [
                'status' => 'failed',
                'message' => 'Reminder failed to send. Please check mail configuration.',
            ];
        }
    }

    private function sendGroupedReminderForPic(Collection $sops, string $trigger): array
    {
        if ($sops->isEmpty()) {
            return [
                'status' => 'failed',
                'message' => 'Reminder failed: SOP list is empty.',
            ];
        }

        $sops = $sops->values();
        $sops->loadMissing(['pic', 'category', 'department']);

        $picUserId = (int) ($sops->first()->pic_user_id ?? 0);
        if ($picUserId <= 0 || $sops->contains(static fn (SopDocument $sop): bool => (int) $sop->pic_user_id !== $picUserId)) {
            return [
                'status' => 'failed',
                'message' => 'Reminder failed: grouped SOP data is inconsistent.',
            ];
        }

        $pic = $sops->first()->pic;
        $batchId = (string) Str::uuid();

        $jobs = $sops->map(function (SopDocument $sop) use ($trigger, $batchId, $sops) {
            return ReminderJob::query()->create([
                'sop_id' => $sop->id,
                'pic_user_id' => $sop->pic_user_id,
                'reminder_type' => $sop->status === 'expired' ? 'expired' : 'expiring',
                'status' => 'pending',
                'meta_json' => [
                    'trigger' => $trigger,
                    'mode' => 'grouped_pic',
                    'batch_id' => $batchId,
                    'sop_count' => $sops->count(),
                ],
            ]);
        });

        if (!$pic || !$pic->email) {
            foreach ($jobs as $job) {
                $job->update([
                    'status' => 'failed',
                    'meta_json' => array_merge($job->meta_json ?? [], ['error' => 'PIC email is missing.']),
                ]);
            }

            return [
                'status' => 'failed',
                'message' => 'Reminder failed: PIC email is missing.',
            ];
        }

        try {
            Mail::to($pic->email)->send(new SopGroupedReminderMail($pic, $sops, $batchId));

            foreach ($jobs as $job) {
                $job->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            return [
                'status' => 'sent',
                'message' => 'Grouped reminder email sent.',
                'pic_name' => $pic->name,
                'sop_count' => $sops->count(),
            ];
        } catch (\Throwable $e) {
            foreach ($jobs as $job) {
                $job->update([
                    'status' => 'failed',
                    'meta_json' => array_merge($job->meta_json ?? [], ['error' => $e->getMessage()]),
                ]);
            }

            return [
                'status' => 'failed',
                'message' => 'Grouped reminder failed to send. Please check mail configuration.',
            ];
        }
    }
}
