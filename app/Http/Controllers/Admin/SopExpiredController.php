<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SopReminderMail;
use App\Models\ReminderJob;
use App\Models\SopDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SopExpiredController extends Controller
{
    public function index(Request $request)
    {
        $query = SopDocument::query()
            ->with(['category', 'department', 'pic'])
            ->where('status', 'expired')
            ->orderBy('expiry_date');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('pic_user_id')) {
            $query->where('pic_user_id', $request->integer('pic_user_id'));
        }

        return view('admin.sop.expired', [
            'items' => $query->paginate(20)->withQueryString(),
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

    public function bulkRemind(Request $request)
    {
        $validated = $request->validate([
            'sop_ids' => ['required', 'array', 'min:1'],
            'sop_ids.*' => ['required', 'integer', 'distinct', 'exists:sop_documents,id'],
        ]);

        $ids = collect($validated['sop_ids'])
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $sops = SopDocument::query()
            ->with('pic')
            ->where('status', 'expired')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $sentCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($ids as $id) {
            $sop = $sops->get($id);
            if (!$sop) {
                $skippedCount++;
                continue;
            }

            $result = $this->sendReminderForSop($sop, 'bulk');
            if ($result['status'] === 'sent') {
                $sentCount++;
            } else {
                $failedCount++;
            }
        }

        if ($sentCount > 0 && $failedCount === 0 && $skippedCount === 0) {
            return back()->with('success', "Bulk reminder sent for {$sentCount} SOP(s).");
        }

        if ($sentCount === 0 && $failedCount > 0) {
            return back()->with('error', "Bulk reminder failed for {$failedCount} SOP(s). Please check PIC email or mail configuration.");
        }

        $parts = [];
        if ($sentCount > 0) {
            $parts[] = "sent: {$sentCount}";
        }
        if ($failedCount > 0) {
            $parts[] = "failed: {$failedCount}";
        }
        if ($skippedCount > 0) {
            $parts[] = "skipped: {$skippedCount}";
        }

        return back()->with('warning', 'Bulk reminder completed partially (' . implode(', ', $parts) . ').');
    }

    public function archive(SopDocument $sop)
    {
        $sop->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        return back()->with('success', 'SOP archived.');
    }

    public function export()
    {
        $rows = SopDocument::query()
            ->with(['category', 'department', 'pic'])
            ->where('status', 'expired')
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
}
