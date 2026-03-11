<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReminderJob;
use App\Models\SopDocument;
use Illuminate\Http\Request;

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
        ReminderJob::query()->create([
            'sop_id' => $sop->id,
            'pic_user_id' => $sop->pic_user_id,
            'reminder_type' => $sop->status === 'expired' ? 'expired' : 'expiring',
            'status' => 'pending',
            'meta_json' => ['trigger' => 'manual'],
        ]);

        return back()->with('success', 'Reminder queued.');
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
