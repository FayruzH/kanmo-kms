@extends('layouts.app')

@section('page_title', 'SOP Management')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="mb-1 fw-bold">SOP Management</h2>
            <p class="text-secondary mb-0">Create, edit, and manage all SOP records.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-danger px-4 py-2 rounded-3 d-none" id="bulkDeleteBtn">
                <i class="bi bi-trash3 me-1"></i> Delete Selected
            </button>
            <a href="{{ route('admin.sop.export', request()->query()) }}" class="btn btn-outline-secondary px-4 py-2 rounded-3">
                <i class="bi bi-download me-1"></i> Download
            </a>
            <a href="{{ route('admin.sop.create') }}" class="btn btn-primary px-4 py-2 rounded-3">
                <i class="bi bi-plus-lg me-1"></i> Add SOP
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form id="bulkDeleteForm" method="POST" action="{{ route('admin.sop.bulk-destroy') }}" class="d-none">
        @csrf
        @method('DELETE')
        <div id="bulkDeleteInputs"></div>
    </form>

    <form method="GET" action="{{ route('admin.sop.index') }}" class="card border-0 shadow-sm rounded-4 mb-3" data-auto-submit>
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label small text-secondary mb-1">Search</label>
                    <div class="input-group kms-search-line">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search title or summary..." value="{{ request('search') }}" data-auto-submit-input>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small text-secondary mb-1">Status</label>
                    <select name="status" class="form-select" data-auto-submit-select>
                        <option value="">All Status</option>
                        @foreach (['active', 'expiring_soon', 'expired', 'archived'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small text-secondary mb-1">Division</label>
                    <select name="category_id" class="form-select" data-auto-submit-select>
                        <option value="">All Divisions</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small text-secondary mb-1">Department</label>
                    <select name="department_id" class="form-select" data-auto-submit-select>
                        <option value="">All Department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small text-secondary">{{ number_format($items->total()) }} SOP found</span>
    </div>

    <div class="kms-table-wrap">
        <table class="table kms-table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:36px;">
                        <input type="checkbox" class="form-check-input" id="selectAllSop">
                    </th>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Division</th>
                    <th>Department</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Expiry</th>
                    <th>PIC</th>
                    <th style="width:90px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input sop-select" value="{{ $item->id }}">
                        </td>
                        <td class="text-secondary small">SOP-{{ str_pad((string) $item->id, 3, '0', STR_PAD_LEFT) }}</td>
                        <td class="fw-semibold">{{ $item->title }}</td>
                        <td>{{ $item->category?->name }}</td>
                        <td>{{ $item->department?->name }}</td>
                        <td><i class="bi {{ $item->type === 'url' ? 'bi-box-arrow-up-right text-info' : 'bi-file-earmark-text text-success' }}"></i></td>
                        <td><span class="status-pill status-{{ $item->status }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td>
                        <td>{{ optional($item->expiry_date)->format('M j, Y') }}</td>
                        <td>{{ $item->pic?->name }}</td>
                        <td>
                            <div class="d-flex">
                                <a href="{{ route('admin.sop.edit', $item) }}" class="btn btn-sm btn-light" title="Edit SOP">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-secondary">No SOP found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
</div>

<script>
    (function () {
        const selectAllSop = document.getElementById('selectAllSop');
        const rowChecks = Array.from(document.querySelectorAll('.sop-select'));
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');
        const bulkDeleteInputs = document.getElementById('bulkDeleteInputs');

        function syncBulkDeleteUI() {
            if (!bulkDeleteBtn) return;
            const selected = rowChecks.filter((checkbox) => checkbox.checked);
            const selectedCount = selected.length;
            bulkDeleteBtn.classList.toggle('d-none', selectedCount === 0);
            bulkDeleteBtn.disabled = selectedCount === 0;
            bulkDeleteBtn.innerHTML = `<i class="bi bi-trash3 me-1"></i> Delete Selected (${selectedCount})`;

            if (selectAllSop) {
                const total = rowChecks.length;
                selectAllSop.checked = total > 0 && selectedCount === total;
                selectAllSop.indeterminate = selectedCount > 0 && selectedCount < total;
            }
        }

        if (selectAllSop) {
            selectAllSop.addEventListener('change', function () {
                rowChecks.forEach((checkbox) => {
                    checkbox.checked = selectAllSop.checked;
                });
                syncBulkDeleteUI();
            });
        }

        rowChecks.forEach((checkbox) => {
            checkbox.addEventListener('change', syncBulkDeleteUI);
        });

        if (bulkDeleteBtn && bulkDeleteForm && bulkDeleteInputs) {
            bulkDeleteBtn.addEventListener('click', function () {
                const selected = rowChecks.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);
                if (selected.length === 0) return;

                if (!confirm(`Delete ${selected.length} selected SOP(s)?`)) return;

                bulkDeleteInputs.innerHTML = '';
                selected.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'sop_ids[]';
                    input.value = id;
                    bulkDeleteInputs.appendChild(input);
                });

                bulkDeleteForm.submit();
            });
        }

        syncBulkDeleteUI();
    })();
</script>
@endsection
