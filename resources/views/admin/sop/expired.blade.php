@extends('layouts.app')

@section('page_title', 'Expired SOPs')

@section('content')
@php
    $remindPicAction = \Illuminate\Support\Facades\Route::has('admin.sop.expired.remind-pic')
        ? route('admin.sop.expired.remind-pic', [], false)
        : route('admin.sop.expired.remind-selected', [], false);
    $activeScope = array_filter([
        'status' => request('status'),
        'category_id' => request('category_id'),
        'department_id' => request('department_id'),
    ], static fn ($value) => filled($value));
    $picGroupSummary = [
        'pic_total' => (int) $picGroups->count(),
        'expired_total' => (int) $picGroups->sum('expired_total'),
        'expiring_total' => (int) $picGroups->sum('expiring_total'),
        'sop_total' => (int) $picGroups->sum('total'),
    ];
    $perPageOptions = [20, 50, 100, 500, 1000];
    $selectedPerPage = (int) request('per_page', $perPageOptions[0]);
    if (!in_array($selectedPerPage, $perPageOptions, true)) {
        $selectedPerPage = $perPageOptions[0];
    }
@endphp
<style>
    .kms-pic-group-card .kms-pic-avatar {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 0.82rem;
        font-weight: 700;
        color: #1e3a8a;
        background: #e8efff;
    }

    .kms-pic-group-card .kms-pic-metric {
        border: 1px solid #e9edf6;
        border-radius: 12px;
        padding: 10px 12px;
        background: #f9fbff;
    }

    .kms-pic-group-card .kms-pic-metric-label {
        color: #6b7280;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .kms-pic-group-card .kms-pic-metric-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1;
    }

    .kms-pic-group-card .kms-count-badge {
        min-width: 70px;
        text-align: center;
        font-weight: 600;
        border-radius: 999px;
        padding: 6px 10px;
        display: inline-block;
    }

    .kms-pic-group-card .kms-count-expired {
        background: #fdecec;
        color: #c81e1e;
    }

    .kms-pic-group-card .kms-count-expiring {
        background: #fff7e8;
        color: #b45309;
    }

    .kms-pic-group-card .kms-count-total {
        background: #eef2ff;
        color: #3730a3;
    }

    .kms-pic-group-card .kms-pic-group-table td,
    .kms-pic-group-card .kms-pic-group-table th {
        padding-top: 0.72rem;
        padding-bottom: 0.72rem;
        vertical-align: middle;
    }

    @media (max-width: 991.98px) {
        .kms-pic-group-card .kms-pic-group-table {
            min-width: 720px;
        }
    }
</style>
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Expired & Expiring SOPs</h2>
            <p class="text-secondary mb-0">{{ $items->total() }} SOPs require attention.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary px-4 d-none" id="bulkRemindBtn">
                <i class="bi bi-envelope me-1"></i>Send Group Reminder
            </button>
            <a href="{{ route('admin.sop.expired.export', request()->query()) }}" class="btn btn-outline-secondary px-4"><i class="bi bi-download me-1"></i>Export List</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.sop.expired.index') }}" class="card border-0 shadow-sm rounded-4 mb-3" data-auto-submit>
        <input type="hidden" name="per_page" value="{{ $selectedPerPage }}">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-lg-3">
                    <label class="form-label small text-secondary mb-1">Status</label>
                    <select name="status" class="form-select" data-auto-submit-select>
                        <option value="">All Reminder Status</option>
                        @foreach (['expired', 'expiring_soon'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label small text-secondary mb-1">Division</label>
                    <select name="category_id" class="form-select" data-auto-submit-select>
                        <option value="">All Divisions</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label small text-secondary mb-1">Department</label>
                    <select name="department_id" class="form-select" data-auto-submit-select>
                        <option value="">All Departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label small text-secondary mb-1">PIC</label>
                    <select name="pic_user_id" class="form-select" data-auto-submit-select>
                        <option value="">All PIC</option>
                        @foreach ($pics as $pic)
                            <option value="{{ $pic->id }}" @selected((string) request('pic_user_id') === (string) $pic->id)>{{ $pic->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    @if (filled(request('pic_user_id')))
        <form method="POST" action="{{ $remindPicAction }}" class="mb-3">
            @csrf
            <input type="hidden" name="pic_user_id" value="{{ request('pic_user_id') }}">
            @foreach ($activeScope as $scopeKey => $scopeValue)
                <input type="hidden" name="{{ $scopeKey }}" value="{{ $scopeValue }}">
            @endforeach
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send me-1"></i>Send Reminder to This PIC (All Expired/Expiring SOP)
            </button>
        </form>
    @endif

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

    <div class="card border-0 shadow-sm rounded-4 mb-3 kms-pic-group-card">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h6 class="mb-1 fw-bold"><i class="bi bi-people-fill me-1 text-primary"></i>Grouped by PIC</h6>
                    <p class="small text-secondary mb-0">Send one consolidated reminder to each PIC for all expired or expiring SOPs.</p>
                </div>
                <span class="badge border text-secondary bg-light-subtle">{{ number_format($picGroupSummary['pic_total']) }} PIC(s)</span>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6 col-lg-3">
                    <div class="kms-pic-metric">
                        <div class="kms-pic-metric-label">PIC Groups</div>
                        <div class="kms-pic-metric-value">{{ number_format($picGroupSummary['pic_total']) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="kms-pic-metric">
                        <div class="kms-pic-metric-label">Expired SOP</div>
                        <div class="kms-pic-metric-value text-danger">{{ number_format($picGroupSummary['expired_total']) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="kms-pic-metric">
                        <div class="kms-pic-metric-label">Expiring Soon</div>
                        <div class="kms-pic-metric-value text-warning">{{ number_format($picGroupSummary['expiring_total']) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="kms-pic-metric">
                        <div class="kms-pic-metric-label">Total SOP</div>
                        <div class="kms-pic-metric-value">{{ number_format($picGroupSummary['sop_total']) }}</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 kms-pic-group-table">
                    <thead class="table-light">
                        <tr>
                            <th>PIC</th>
                            <th class="text-end">Expired</th>
                            <th class="text-end">Expiring Soon</th>
                            <th class="text-end">Total</th>
                            <th class="text-end" style="width:260px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($picGroups as $group)
                            @php
                                $picName = $group->pic?->name ?? 'PIC missing';
                                $picEmail = $group->pic?->email ?? 'No email';
                                $picInitial = strtoupper(substr($picName, 0, 1));
                                $picFilterQuery = array_merge(request()->query(), ['pic_user_id' => $group->pic_user_id]);
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="kms-pic-avatar">{{ $picInitial }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $picName }}</div>
                                            <div class="small text-secondary">{{ $picEmail }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"><span class="kms-count-badge kms-count-expired">{{ number_format((int) $group->expired_total) }}</span></td>
                                <td class="text-end"><span class="kms-count-badge kms-count-expiring">{{ number_format((int) $group->expiring_total) }}</span></td>
                                <td class="text-end"><span class="kms-count-badge kms-count-total">{{ number_format((int) $group->total) }}</span></td>
                                <td class="text-end">
                                    @if ($group->pic_user_id)
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            <form method="POST" action="{{ $remindPicAction }}">
                                                @csrf
                                                <input type="hidden" name="pic_user_id" value="{{ $group->pic_user_id }}">
                                                @foreach ($activeScope as $scopeKey => $scopeValue)
                                                    <input type="hidden" name="{{ $scopeKey }}" value="{{ $scopeValue }}">
                                                @endforeach
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-send me-1"></i>Send Reminder
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.sop.expired.index', $picFilterQuery) }}" class="btn btn-sm btn-light border">
                                                <i class="bi bi-eye me-1"></i>View SOP
                                            </a>
                                        </div>
                                    @else
                                        <span class="small text-secondary">PIC missing</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No grouped PIC data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope me-1"></i>Remind SOP</button>
                                </form>
                                @if ($item->pic_user_id)
                                    <form method="POST" action="{{ $remindPicAction }}">
                                        @csrf
                                        <input type="hidden" name="pic_user_id" value="{{ $item->pic_user_id }}">
                                        @foreach ($activeScope as $scopeKey => $scopeValue)
                                            <input type="hidden" name="{{ $scopeKey }}" value="{{ $scopeValue }}">
                                        @endforeach
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-send me-1"></i>Remind PIC</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.sop.expired.archive', $item) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light text-danger"><i class="bi bi-archive"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4 text-secondary">No expired/expiring SOP.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <form method="GET" action="{{ route('admin.sop.expired.index') }}" class="d-flex align-items-center gap-2">
            @foreach (request()->except(['per_page', 'page']) as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $arrayValue)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $arrayValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label for="perPageSelectExpired" class="small text-secondary mb-0">Show per page</label>
            <select id="perPageSelectExpired" name="per_page" class="form-select form-select-sm" style="width: 110px;" onchange="this.form.submit()">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($selectedPerPage === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </form>

        <div>{{ $items->links() }}</div>
    </div>
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
            bulkRemindBtn.innerHTML = `<i class="bi bi-envelope me-1"></i>Send Group Reminder (${selectedCount})`;

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

                if (!confirm(`Send grouped reminder by PIC for ${selected.length} selected SOP(s)?`)) return;

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
