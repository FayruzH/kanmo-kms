@csrf
@php
    $selectedType = old('type', $sop->type ?? 'file');
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Title *</label>
        <input type="text" name="title" class="form-control" placeholder="SOP Title" value="{{ old('title', $sop->title ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Category *</label>
        <select name="category_id" class="form-select" required>
            <option value="">Select category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $sop->category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Department *</label>
        <select name="department_id" class="form-select" required>
            <option value="">Select department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $sop->department_id ?? '') === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Entity</label>
        <input type="text" name="entity" class="form-control" placeholder="e.g. Kanmo Group" value="{{ old('entity', $sop->entity ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">SOP Type *</label>
        <select name="type" id="sopTypeSelect" class="form-select" required>
            <option value="url" @selected($selectedType === 'url')>URL Link</option>
            <option value="file" @selected($selectedType === 'file')>File Upload</option>
        </select>
    </div>
    <div class="col-md-6" id="urlFieldWrap">
        <label class="form-label fw-semibold">URL *</label>
        <input type="url" name="url" id="urlInputField" class="form-control" placeholder="https://..." value="{{ old('url', $sop->url ?? '') }}">
    </div>
    <div class="col-md-6" id="fileFieldWrap">
        <label class="form-label fw-semibold">File *</label>
        <input type="file" name="file" id="fileInputField" class="form-control">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Source App</label>
        <select name="source_app_id" class="form-select">
            <option value="">Select source</option>
            @foreach ($sourceApps as $sourceApp)
                <option value="{{ $sourceApp->id }}" @selected((string) old('source_app_id', $sop->source_app_id ?? '') === (string) $sourceApp->id)>{{ $sourceApp->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Source Name</label>
        <input type="text" name="source_name" class="form-control" placeholder="e.g. Store Ops Library" value="{{ old('source_name', $sop->source_name ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Version *</label>
        <input type="text" name="version" class="form-control" placeholder="e.g. 1.0" value="{{ old('version', $sop->version ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Effective Date *</label>
        <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date', isset($sop->effective_date) ? $sop->effective_date->format('Y-m-d') : '') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Expiry Date *</label>
        <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date', isset($sop->expiry_date) ? $sop->expiry_date->format('Y-m-d') : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">PIC (Owner) *</label>
        <select name="pic_user_id" class="form-select" required>
            <option value="">Select or type owner name</option>
            @foreach ($pics as $pic)
                <option value="{{ $pic->id }}" @selected((string) old('pic_user_id', $sop->pic_user_id ?? '') === (string) $pic->id)>{{ $pic->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Tags</label>
        <input type="text" name="tags" class="form-control" placeholder="Comma-separated, e.g. leave, HR, policy" value="{{ old('tags', $tagsText ?? '') }}">
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Summary</label>
        <textarea name="summary" class="form-control" rows="3" placeholder="Brief description...">{{ old('summary', $sop->summary ?? '') }}</textarea>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.sop.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
    <button type="submit" class="btn btn-primary px-4">{{ $submitLabel ?? 'Create SOP' }}</button>
</div>

<script>
    (function () {
        const typeSelect = document.getElementById('sopTypeSelect');
        const urlWrap = document.getElementById('urlFieldWrap');
        const fileWrap = document.getElementById('fileFieldWrap');
        const urlInput = document.getElementById('urlInputField');
        const fileInput = document.getElementById('fileInputField');
        if (!typeSelect || !urlWrap || !fileWrap) return;

        function syncTypeUI() {
            const isUrl = typeSelect.value === 'url';
            urlWrap.style.display = isUrl ? 'block' : 'none';
            fileWrap.style.display = isUrl ? 'none' : 'block';
            if (urlInput) urlInput.required = isUrl;
            if (fileInput) fileInput.required = !isUrl;
        }

        typeSelect.addEventListener('change', syncTypeUI);
        syncTypeUI();
    })();
</script>
