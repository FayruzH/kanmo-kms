@extends('layouts.app')

@section('page_title', 'Expired SOPs')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Expired & Expiring SOPs</h2>
            <p class="text-secondary mb-0">{{ $items->total() }} SOPs require attention.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary px-4 d-none" id="bulkRemindBtn">
                <i class="bi bi-envelope me-1"></i>Remind Selected
            </button>
            <a href="{{ route('admin.sop.expired.export') }}" class="btn btn-outline-secondary px-4"><i class="bi bi-download me-1"></i>Export List</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    <form id="bulkRemindForm" method="POST" action="{{ route('admin.sop.expired.remind-selected') }}" class="d-none">
        @csrf
        <div id="bulkRemindInputs"></div>
    </form>

    <div class="kms-table-wrap">
        <table class="table kms-table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:36px;">
                        <input type="checkbox" class="form-check-input" id="selectAllExpired">
                    </th>
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
                            <input type="checkbox" class="form-check-input expired-select" value="{{ $item->id }}">
                        </td>
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
                    <tr><td colspan="8" class="text-center py-4 text-secondary">No expired SOP.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $items->links() }}</div>
</div>

<script>
    (function () {
        const selectAllExpired = document.getElementById('selectAllExpired');
        const rowChecks = Array.from(document.querySelectorAll('.expired-select'));
        const bulkRemindBtn = document.getElementById('bulkRemindBtn');
        const bulkRemindForm = document.getElementById('bulkRemindForm');
        const bulkRemindInputs = document.getElementById('bulkRemindInputs');

        function syncBulkRemindUI() {
            if (!bulkRemindBtn) return;

            const selected = rowChecks.filter((checkbox) => checkbox.checked);
            const selectedCount = selected.length;
            bulkRemindBtn.classList.toggle('d-none', selectedCount === 0);
            bulkRemindBtn.disabled = selectedCount === 0;
            bulkRemindBtn.innerHTML = `<i class="bi bi-envelope me-1"></i>Remind Selected (${selectedCount})`;

            if (selectAllExpired) {
                const total = rowChecks.length;
                selectAllExpired.checked = total > 0 && selectedCount === total;
                selectAllExpired.indeterminate = selectedCount > 0 && selectedCount < total;
            }
        }

        if (selectAllExpired) {
            selectAllExpired.addEventListener('change', function () {
                rowChecks.forEach((checkbox) => {
                    checkbox.checked = selectAllExpired.checked;
                });
                syncBulkRemindUI();
            });
        }

        rowChecks.forEach((checkbox) => {
            checkbox.addEventListener('change', syncBulkRemindUI);
        });

        if (bulkRemindBtn && bulkRemindForm && bulkRemindInputs) {
            bulkRemindBtn.addEventListener('click', function () {
                const selected = rowChecks.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);
                if (selected.length === 0) return;

                if (!confirm(`Send reminder for ${selected.length} selected SOP(s)?`)) return;

                bulkRemindInputs.innerHTML = '';
                selected.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'sop_ids[]';
                    input.value = id;
                    bulkRemindInputs.appendChild(input);
                });

                bulkRemindForm.submit();
            });
        }

        syncBulkRemindUI();
    })();
</script>
@endsection
