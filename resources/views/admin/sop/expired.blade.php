@extends('layouts.app')

@section('page_title', 'Expired SOPs')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Expired & Expiring SOPs</h2>
            <p class="text-secondary mb-0">{{ $items->total() }} SOPs require attention.</p>
        </div>
        <a href="{{ route('admin.sop.expired.export') }}" class="btn btn-outline-secondary px-4"><i class="bi bi-download me-1"></i>Export List</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="kms-table-wrap">
        <table class="table kms-table align-middle mb-0">
            <thead>
                <tr>
                    <th>SOP</th>
                    <th>Department</th>
                    <th>PIC</th>
                    <th>Status</th>
                    <th>Expiry Date</th>
                    <th>Overdue</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    @php
                        $days = $item->expiry_date ? now()->startOfDay()->diffInDays($item->expiry_date->startOfDay(), false) : null;
                        $overdueText = $days === null ? '-' : ($days < 0 ? abs($days) . 'd overdue' : $days . 'd left');
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $item->title }}</div>
                            <div class="small text-secondary">SOP-{{ str_pad((string) $item->id, 3, '0', STR_PAD_LEFT) }}</div>
                        </td>
                        <td>{{ $item->department?->name }}</td>
                        <td>{{ $item->pic?->name }}</td>
                        <td><span class="status-pill status-{{ $item->status }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td>
                        <td>{{ optional($item->expiry_date)->format('M j, Y') }}</td>
                        <td class="{{ $days !== null && $days < 0 ? 'text-danger' : 'text-warning' }}">{{ $overdueText }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('admin.sop.expired.remind', $item) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope me-1"></i>Remind</button>
                                </form>
                                <form method="POST" action="{{ route('admin.sop.expired.archive', $item) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-light text-danger"><i class="bi bi-archive"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-secondary">No expired SOP.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection
