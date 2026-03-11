@extends('layouts.app')

@section('page_title', 'SOP Management')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1 fw-bold">SOP Management</h2>
            <p class="text-secondary mb-0">Create, edit, and manage all SOP records.</p>
        </div>
        <button class="btn btn-primary px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#addSopModal">
            <i class="bi bi-plus-lg me-1"></i> Add SOP
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.sop.index') }}" class="mb-3">
        <div class="input-group kms-search-line">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search by title or ID..." value="{{ request('search') }}">
        </div>
    </form>

    <div class="kms-table-wrap">
        <table class="table kms-table align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Expiry</th>
                    <th>PIC</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td class="text-secondary small">SOP-{{ str_pad((string) $item->id, 3, '0', STR_PAD_LEFT) }}</td>
                        <td class="fw-semibold">{{ $item->title }}</td>
                        <td>{{ $item->department?->name }}</td>
                        <td>{{ $item->category?->name }}</td>
                        <td><i class="bi {{ $item->type === 'url' ? 'bi-box-arrow-up-right text-info' : 'bi-file-earmark-text text-success' }}"></i></td>
                        <td><span class="status-pill status-{{ $item->status }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td>
                        <td>{{ optional($item->expiry_date)->format('M j, Y') }}</td>
                        <td>{{ $item->pic?->name }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.sop.edit', $item) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.sop.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this SOP?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-secondary">No SOP found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
</div>

<div class="modal fade" id="addSopModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Add New SOP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.sop.store') }}" method="POST" enctype="multipart/form-data" class="px-4 pb-4" id="addSopForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="SOP Title" value="{{ old('title') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Category *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Department *</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">Select department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Entity</label>
                        <input type="text" name="entity" class="form-control" placeholder="e.g. Kanmo Group" value="{{ old('entity') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">SOP Type *</label>
                        <select name="type" id="modalSopType" class="form-select" required>
                            <option value="url" @selected(old('type') === 'url')>URL Link</option>
                            <option value="file" @selected(old('type', 'file') === 'file')>File Upload</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="modalFileWrap">
                        <label class="form-label fw-semibold">File *</label>
                        <input type="file" name="file" id="modalFileInput" class="form-control">
                    </div>
                    <div class="col-md-6" id="modalUrlWrap">
                        <label class="form-label fw-semibold">URL *</label>
                        <input type="url" name="url" id="modalUrlInput" class="form-control" placeholder="https://..." value="{{ old('url') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Source App</label>
                        <select name="source_app_id" class="form-select">
                            <option value="">Select source</option>
                            @foreach ($sourceApps as $sourceApp)
                                <option value="{{ $sourceApp->id }}" @selected((string) old('source_app_id') === (string) $sourceApp->id)>{{ $sourceApp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Source Name</label>
                        <input type="text" name="source_name" class="form-control" placeholder="e.g. Store Ops Library" value="{{ old('source_name') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Version *</label>
                        <input type="text" name="version" class="form-control" placeholder="e.g. 1.0" value="{{ old('version') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Effective Date *</label>
                        <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Expiry Date *</label>
                        <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">PIC (Owner) *</label>
                        <select name="pic_user_id" class="form-select" required>
                            <option value="">Select or type owner name</option>
                            @foreach ($pics as $pic)
                                <option value="{{ $pic->id }}" @selected((string) old('pic_user_id') === (string) $pic->id)>{{ $pic->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Tags</label>
                        <input type="text" name="tags" class="form-control" placeholder="Comma-separated, e.g. leave, HR, policy" value="{{ old('tags') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Summary</label>
                        <textarea name="summary" class="form-control" rows="3" placeholder="Brief description...">{{ old('summary') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Create SOP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        const typeSelect = document.getElementById('modalSopType');
        const urlWrap = document.getElementById('modalUrlWrap');
        const fileWrap = document.getElementById('modalFileWrap');
        const urlInput = document.getElementById('modalUrlInput');
        const fileInput = document.getElementById('modalFileInput');

        function syncTypeUI() {
            if (!typeSelect) return;
            const isUrl = typeSelect.value === 'url';
            if (urlWrap) urlWrap.style.display = isUrl ? 'block' : 'none';
            if (fileWrap) fileWrap.style.display = isUrl ? 'none' : 'block';
            if (urlInput) urlInput.required = isUrl;
            if (fileInput) fileInput.required = !isUrl;
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', syncTypeUI);
            syncTypeUI();
        }

        @if ($errors->any())
        const modalEl = document.getElementById('addSopModal');
        if (modalEl && window.bootstrap) {
            new bootstrap.Modal(modalEl).show();
        }
        @endif
    })();
</script>
@endsection
