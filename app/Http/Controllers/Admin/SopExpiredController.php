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
        $sop->loadMissing('pic');

        $job = ReminderJob::query()->create([
            'sop_id' => $sop->id,
            'pic_user_id' => $sop->pic_user_id,
            'reminder_type' => $sop->status === 'expired' ? 'expired' : 'expiring',
            'status' => 'pending',
            'meta_json' => ['trigger' => 'manual'],
        ]);

        if (!$sop->pic || !$sop->pic->email) {
            $job->update([
                'status' => 'failed',
                'meta_json' => array_merge($job->meta_json ?? [], ['error' => 'PIC email is missing.']),
            ]);

            return back()->with('error', 'Reminder failed: PIC email is missing.');
        }

        try {
            Mail::to($sop->pic->email)->send(new SopReminderMail($sop, $job->reminder_type));
            $job->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return back()->with('success', 'Reminder email sent.');
        } catch (\Throwable $e) {
            $job->update([
                'status' => 'failed',
                'meta_json' => array_merge($job->meta_json ?? [], ['error' => $e->getMessage()]),
            ]);

            return back()->with('error', 'Reminder failed to send. Please check mail configuration.');
        }
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
        fputcsv($handle, ['title', 'category', 'department', 'pic', 'expiry_date', 'status']);
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
}
