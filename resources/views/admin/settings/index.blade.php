@extends('layouts.app')

@section('page_title', 'Settings')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Settings</h2>
        <p class="text-secondary mb-0">Configure system-wide KMS settings.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="kms-table-wrap p-4" style="max-width: 980px;">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="mb-2">
                <label class="form-label fw-semibold">Expiry Threshold (days)</label>
                <div class="text-secondary mb-2">SOPs expiring within this many days will show as "Expiring Soon".</div>
                <input type="number" min="1" max="365" class="form-control" style="max-width: 360px;" name="expiry_threshold_days" value="{{ old('expiry_threshold_days', $expiryThreshold) }}">
            </div>

            @if($errors->settingsMain->any())
                <div class="alert alert-danger py-2 mb-2">
                    {{ $errors->settingsMain->first() }}
                </div>
            @endif

            <button class="btn btn-primary px-4"><i class="bi bi-floppy me-1"></i>Save Settings</button>
        </form>

        <hr class="kms-detail-divider my-4">

        <div class="kms-settings-stack">
            <details class="kms-settings-dropdown" open>
                <summary>
                    <div>
                        <h4 class="h5 fw-semibold mb-1">Divisions</h4>
                        <div class="text-secondary">Add, edit, or remove SOP divisions.</div>
                    </div>
                    <span class="kms-settings-count">{{ $categories->count() }} items</span>
                </summary>
                <div class="kms-settings-body">
                    @if($errors->categoryCreate->any())
                        <div class="alert alert-danger py-2 mb-3">{{ $errors->categoryCreate->first() }}</div>
                    @endif
                    @if($errors->categoryUpdate->any())
                        <div class="alert alert-danger py-2 mb-3">{{ $errors->categoryUpdate->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.categories.store') }}" class="row g-2 align-items-end mb-3">
                        @csrf
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small mb-1">New division</label>
                            <input type="text" class="form-control" name="name" placeholder="Input division name" required>
                        </div>
                        <div class="col-md-4 d-grid">
                            <button class="btn btn-outline-primary"><i class="bi bi-plus-circle me-1"></i>Add Division</button>
                        </div>
                    </form>

                    @if($categories->isNotEmpty())
                        <div class="kms-settings-manager"
                             data-settings-manager
                             data-update-template="{{ route('admin.settings.categories.update', ['category' => 'ID_PLACEHOLDER']) }}"
                             data-delete-template="{{ route('admin.settings.categories.destroy', ['category' => 'ID_PLACEHOLDER']) }}">
                            <form method="POST" data-settings-update-form class="row g-2 align-items-end">
                                @csrf
                                @method('PUT')
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small mb-1">Select division</label>
                                    <select class="form-select" data-settings-select>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" data-name="{{ $category->name }}" data-count="{{ $category->documents_count }}">
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small mb-1">Rename selected</label>
                                    <input type="text" class="form-control" name="name" data-settings-name required>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-outline-primary" data-settings-update-btn>Update</button>
                                </div>
                            </form>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="kms-settings-meta" data-settings-usage>0 SOP</span>
                                <form method="POST" data-settings-delete-form onsubmit="return confirm('Remove selected division from active list?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-settings-delete-btn><i class="bi bi-trash me-1"></i>Remove Selected</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="text-secondary small">No active divisions yet.</div>
                    @endif
                </div>
            </details>

            <details class="kms-settings-dropdown">
                <summary>
                    <div>
                        <h4 class="h5 fw-semibold mb-1">Departments</h4>
                        <div class="text-secondary">Add, edit, or remove departments.</div>
                    </div>
                    <span class="kms-settings-count">{{ $departments->count() }} items</span>
                </summary>
                <div class="kms-settings-body">
                    @if($errors->departmentCreate->any())
                        <div class="alert alert-danger py-2 mb-3">{{ $errors->departmentCreate->first() }}</div>
                    @endif
                    @if($errors->departmentUpdate->any())
                        <div class="alert alert-danger py-2 mb-3">{{ $errors->departmentUpdate->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.departments.store') }}" class="row g-2 align-items-end mb-3">
                        @csrf
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small mb-1">New department</label>
                            <input type="text" class="form-control" name="name" placeholder="Input department name" required>
                        </div>
                        <div class="col-md-4 d-grid">
                            <button class="btn btn-outline-primary"><i class="bi bi-plus-circle me-1"></i>Add Department</button>
                        </div>
                    </form>

                    @if($departments->isNotEmpty())
                        <div class="kms-settings-manager"
                             data-settings-manager
                             data-update-template="{{ route('admin.settings.departments.update', ['department' => 'ID_PLACEHOLDER']) }}"
                             data-delete-template="{{ route('admin.settings.departments.destroy', ['department' => 'ID_PLACEHOLDER']) }}">
                            <form method="POST" data-settings-update-form class="row g-2 align-items-end">
                                @csrf
                                @method('PUT')
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small mb-1">Select department</label>
                                    <select class="form-select" data-settings-select>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" data-name="{{ $department->name }}" data-count="{{ $department->documents_count }}">
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small mb-1">Rename selected</label>
                                    <input type="text" class="form-control" name="name" data-settings-name required>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-outline-primary" data-settings-update-btn>Update</button>
                                </div>
                            </form>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="kms-settings-meta" data-settings-usage>0 SOP</span>
                                <form method="POST" data-settings-delete-form onsubmit="return confirm('Remove selected department from active list?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-settings-delete-btn><i class="bi bi-trash me-1"></i>Remove Selected</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="text-secondary small">No active departments yet.</div>
                    @endif
                </div>
            </details>

            <details class="kms-settings-dropdown">
                <summary>
                    <div>
                        <h4 class="h5 fw-semibold mb-1">Source Apps</h4>
                        <div class="text-secondary">Manage external applications linked as SOP sources.</div>
                    </div>
                    <span class="kms-settings-count">{{ $sourceApps->count() }} items</span>
                </summary>
                <div class="kms-settings-body">
                    @if($errors->sourceAppCreate->any())
                        <div class="alert alert-danger py-2 mb-3">{{ $errors->sourceAppCreate->first() }}</div>
                    @endif
                    @if($errors->sourceAppUpdate->any())
                        <div class="alert alert-danger py-2 mb-3">{{ $errors->sourceAppUpdate->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.source_apps.store') }}" class="row g-2 align-items-end mb-3">
                        @csrf
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small mb-1">New source app</label>
                            <input type="text" class="form-control" name="name" placeholder="Input source app name" required>
                        </div>
                        <div class="col-md-4 d-grid">
                            <button class="btn btn-outline-primary"><i class="bi bi-plus-circle me-1"></i>Add Source App</button>
                        </div>
                    </form>

                    @if($sourceApps->isNotEmpty())
                        <div class="kms-settings-manager"
                             data-settings-manager
                             data-update-template="{{ route('admin.settings.source_apps.update', ['sourceApp' => 'ID_PLACEHOLDER']) }}"
                             data-delete-template="{{ route('admin.settings.source_apps.destroy', ['sourceApp' => 'ID_PLACEHOLDER']) }}">
                            <form method="POST" data-settings-update-form class="row g-2 align-items-end">
                                @csrf
                                @method('PUT')
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small mb-1">Select source app</label>
                                    <select class="form-select" data-settings-select>
                                        @foreach($sourceApps as $sourceApp)
                                            <option value="{{ $sourceApp->id }}" data-name="{{ $sourceApp->name }}" data-count="{{ $sourceApp->documents_count }}">
                                                {{ $sourceApp->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small mb-1">Rename selected</label>
                                    <input type="text" class="form-control" name="name" data-settings-name required>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-outline-primary" data-settings-update-btn>Update</button>
                                </div>
                            </form>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="kms-settings-meta" data-settings-usage>0 SOP</span>
                                <form method="POST" data-settings-delete-form onsubmit="return confirm('Remove selected source app from active list?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-settings-delete-btn><i class="bi bi-trash me-1"></i>Remove Selected</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="text-secondary small">No active source apps yet.</div>
                    @endif
                </div>
            </details>
        </div>
    </div>
</div>

<script>
    (function () {
        document.querySelectorAll('[data-settings-manager]').forEach(function (manager) {
            const select = manager.querySelector('[data-settings-select]');
            const nameInput = manager.querySelector('[data-settings-name]');
            const usage = manager.querySelector('[data-settings-usage]');
            const updateForm = manager.querySelector('[data-settings-update-form]');
            const deleteForm = manager.querySelector('[data-settings-delete-form]');
            const updateBtn = manager.querySelector('[data-settings-update-btn]');
            const deleteBtn = manager.querySelector('[data-settings-delete-btn]');
            const updateTemplate = manager.dataset.updateTemplate || '';
            const deleteTemplate = manager.dataset.deleteTemplate || '';

            const sync = function () {
                const option = select.options[select.selectedIndex];
                const id = option ? option.value : '';
                const name = option ? option.dataset.name : '';
                const count = option ? option.dataset.count : '0';

                nameInput.value = name || '';
                usage.textContent = (count || '0') + ' SOP';

                if (!id) {
                    updateBtn.disabled = true;
                    deleteBtn.disabled = true;
                    return;
                }

                updateForm.action = updateTemplate.replace('ID_PLACEHOLDER', id);
                deleteForm.action = deleteTemplate.replace('ID_PLACEHOLDER', id);
                updateBtn.disabled = false;
                deleteBtn.disabled = false;
            };

            select.addEventListener('change', sync);
            sync();
        });
    })();
</script>
@endsection
