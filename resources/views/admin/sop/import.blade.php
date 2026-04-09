@extends('layouts.app')

@section('page_title', 'Bulk Import')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Bulk Import SOPs</h2>
        <p class="text-secondary mb-0">Upload one file to create multiple SOP entries at once.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-start gap-2">
            <i class="bi bi-check-circle-fill mt-1"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-circle-fill mt-1"></i>
            <div>{{ session('warning') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>
                <div class="fw-semibold mb-1">Import failed. Please fix the following:</div>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="kms-table-wrap p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h4 class="fw-semibold mb-1"><i class="bi bi-file-earmark-arrow-down text-success me-2"></i>Download Import Template</h4>
                <div class="text-secondary">Use the official template so the import parser can validate data correctly.</div>
                <div class="small text-secondary mt-2">Required columns: <strong>Title</strong>, <strong>Division</strong>, <strong>Department</strong>, <strong>Expiry Date</strong>, <strong>PIC NIP</strong>.</div>
            </div>
            <a href="{{ route('admin.sop.import.template') }}" class="btn btn-outline-secondary px-4">Template</a>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <span class="kms-chip">Title</span>
            <span class="kms-chip">Division</span>
            <span class="kms-chip">Department</span>
            <span class="kms-chip">Entity</span>
            <span class="kms-chip">Source</span>
            <span class="kms-chip">Type</span>
            <span class="kms-chip">PIC NIP</span>
        </div>
    </div>

    <div class="mb-4">
        <form id="sopImportForm" action="{{ route('admin.sop.import.store') }}" method="POST" enctype="multipart/form-data" class="text-center">
            @csrf
            <input type="file" name="file" id="importFileInput" class="d-none" accept=".csv,.xlsx" required>

            <div class="kms-upload-drop" id="importDropzone" tabindex="0" role="button" aria-label="Upload import file">
                <i class="bi bi-upload display-6 text-secondary"></i>
                <h4 class="fw-semibold mt-3 mb-1">Drop your file here</h4>
                <p class="text-secondary mb-3">Supported formats: <strong>.xlsx</strong> and <strong>.csv</strong>. Max file size follows server upload limits.</p>

                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary px-4" id="pickImportFileBtn">
                        <i class="bi bi-folder2-open me-1"></i>Browse File
                    </button>
                    <button type="submit" class="btn btn-primary px-4" id="startImportBtn" disabled>
                        <i class="bi bi-cloud-arrow-up me-1"></i>Start Import
                    </button>
                </div>

                <div class="kms-upload-file mt-3" id="importSelectedFile">No file selected.</div>
            </div>
        </form>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="h4 fw-bold mb-0">Import History</h3>
        <div class="small text-secondary">{{ number_format($batches->total()) }} batch records</div>
    </div>
    <div class="kms-table-wrap">
        <table class="table kms-table align-middle mb-0">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Imported By</th>
                    <th>Imported At</th>
                    <th>Status</th>
                    <th>Performance</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    @php
                        $totals = is_array($batch->totals_json) ? $batch->totals_json : [];
                        $total = (int) ($totals['total'] ?? $batch->processed_rows_count ?? 0);
                        $success = (int) ($totals['success'] ?? $batch->success_rows_count ?? 0);
                        $failed = (int) ($totals['failed'] ?? $batch->failed_rows_count ?? 0);
                        $successRate = $total > 0 ? round(($success / $total) * 100, 1) : 0;
                        $failedRate = $total > 0 ? round(($failed / $total) * 100, 1) : 0;
                        $failedRows = collect($failedRowsByBatch[$batch->id] ?? []);
                        $importedAtText = $batch->created_at?->format('M j, Y H:i') ?? '-';
                        $failedRowsPayload = $failedRows->map(function ($failedRow) {
                            $raw = is_array($failedRow->raw_json) ? $failedRow->raw_json : [];
                            $title = trim((string) ($raw['title'] ?? $raw['judul'] ?? $raw['sub 3'] ?? '-'));
                            $division = trim((string) ($raw['division'] ?? $raw['category'] ?? $raw['kategori'] ?? $raw['sub 0'] ?? '-'));
                            $department = trim((string) ($raw['department'] ?? $raw['departemen'] ?? $raw['sub 1'] ?? '-'));

                            return [
                                'row_number' => (int) $failedRow->row_number,
                                'error_message' => (string) ($failedRow->error_message ?: 'Unknown error.'),
                                'title' => $title !== '' ? $title : '-',
                                'division' => $division !== '' ? $division : '-',
                                'department' => $department !== '' ? $department : '-',
                            ];
                        })->values()->all();
                        $failedRowsJson = json_encode($failedRowsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                        if ($failedRowsJson === false) {
                            $failedRowsJson = '[]';
                        }

                        if ($total === 0) {
                            $statusLabel = 'Empty';
                            $statusClass = 'is-empty';
                        } elseif ($failed === 0) {
                            $statusLabel = 'Success';
                            $statusClass = 'is-success';
                        } elseif ($success === 0) {
                            $statusLabel = 'Failed';
                            $statusClass = 'is-failed';
                        } else {
                            $statusLabel = 'Partial';
                            $statusClass = 'is-partial';
                        }
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $batch->filename }}</div>
                            <div class="small text-secondary">Batch #{{ $batch->id }}</div>
                        </td>
                        <td>{{ $batch->admin?->name }}</td>
                        <td>
                            <div>{{ $batch->created_at?->format('M j, Y') }}</div>
                            <div class="small text-secondary">{{ $batch->created_at?->format('H:i') }}</div>
                        </td>
                        <td>
                            <span class="kms-import-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td style="min-width: 280px;">
                            <div class="d-flex flex-wrap gap-3 small">
                                <span>Total: <strong>{{ number_format($total) }}</strong></span>
                                <span class="text-success">Success: <strong>{{ number_format($success) }}</strong></span>
                                <span class="text-danger">Failed: <strong>{{ number_format($failed) }}</strong></span>
                            </div>
                            <div class="kms-import-progress mt-2" aria-hidden="true">
                                <span class="is-success" style="width: {{ max(0, min(100, $successRate)) }}%;"></span>
                                <span class="is-failed" style="width: {{ max(0, min(100, $failedRate)) }}%;"></span>
                            </div>
                            <div class="small text-secondary mt-1">{{ number_format((float) $successRate, 1) }}% success rate</div>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm kms-action-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end kms-action-menu">
                                    <li>
                                        <button
                                            type="button"
                                            class="dropdown-item js-view-import-details"
                                            data-bs-toggle="modal"
                                            data-bs-target="#importBatchDetailModal"
                                            data-batch-id="{{ $batch->id }}"
                                            data-batch-file="{{ $batch->filename }}"
                                            data-imported-by="{{ $batch->admin?->name ?? '-' }}"
                                            data-imported-at="{{ $importedAtText }}"
                                            data-status-label="{{ $statusLabel }}"
                                            data-status-class="{{ $statusClass }}"
                                            data-total="{{ $total }}"
                                            data-success="{{ $success }}"
                                            data-failed="{{ $failed }}"
                                            data-success-rate="{{ number_format((float) $successRate, 1, '.', '') }}"
                                        >
                                            View Details
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <script type="application/json" id="batch-failed-rows-{{ $batch->id }}">{!! $failedRowsJson !!}</script>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-secondary">No import history yet.</div>
                            <div class="small text-secondary mt-1">Upload your first file to see history metrics here.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $batches->links() }}</div>

    <div class="modal fade" id="importBatchDetailModal" tabindex="-1" aria-labelledby="importBatchDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-semibold" id="importBatchDetailModalLabel">Import Details</h5>
                        <div class="small text-secondary" id="importBatchDetailMeta">Batch detail</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="kms-modal-stat">
                                <div class="kms-modal-stat-label">Total Rows</div>
                                <div class="kms-modal-stat-value" id="importBatchDetailTotal">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kms-modal-stat">
                                <div class="kms-modal-stat-label">Success</div>
                                <div class="kms-modal-stat-value text-success" id="importBatchDetailSuccess">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kms-modal-stat">
                                <div class="kms-modal-stat-label">Failed</div>
                                <div class="kms-modal-stat-value text-danger" id="importBatchDetailFailed">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kms-modal-stat">
                                <div class="kms-modal-stat-label">Status</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="kms-import-badge is-empty" id="importBatchDetailStatus">-</span>
                                    <span class="small text-secondary" id="importBatchDetailRate">0.0%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-success d-none mb-3" id="importBatchDetailNoErrors">
                        This batch has no failed rows.
                    </div>

                    <div class="table-responsive border rounded-3">
                        <table class="table table-sm align-middle mb-0 kms-import-detail-table">
                            <thead>
                                <tr>
                                    <th style="width: 90px;">Row</th>
                                    <th>Title</th>
                                    <th>Division</th>
                                    <th>Department</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody id="importBatchDetailRows">
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-4">Click "View Details" from an import row.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('sopImportForm');
        if (form) {
            const fileInput = document.getElementById('importFileInput');
            const dropzone = document.getElementById('importDropzone');
            const pickButton = document.getElementById('pickImportFileBtn');
            const submitButton = document.getElementById('startImportBtn');
            const selectedFileLabel = document.getElementById('importSelectedFile');
            const allowedExtensions = ['csv', 'xlsx'];

            if (fileInput && dropzone && pickButton && submitButton && selectedFileLabel) {
                const fileExtension = function (filename) {
                    const segments = (filename || '').toLowerCase().split('.');
                    return segments.length > 1 ? segments.pop() : '';
                };

                const formatFileSize = function (size) {
                    if (size < 1024) return size + ' B';
                    if (size < 1024 * 1024) return (size / 1024).toFixed(1) + ' KB';
                    return (size / (1024 * 1024)).toFixed(1) + ' MB';
                };

                const setFileState = function (file) {
                    dropzone.classList.remove('is-ready', 'is-invalid');
                    submitButton.disabled = true;

                    if (!file) {
                        selectedFileLabel.textContent = 'No file selected.';
                        return;
                    }

                    if (!allowedExtensions.includes(fileExtension(file.name))) {
                        dropzone.classList.add('is-invalid');
                        selectedFileLabel.textContent = 'Invalid file type. Please use .xlsx or .csv.';
                        return;
                    }

                    dropzone.classList.add('is-ready');
                    selectedFileLabel.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                    submitButton.disabled = false;
                };

                const assignDroppedFile = function (file) {
                    if (typeof DataTransfer === 'undefined') {
                        setFileState(file);
                        return;
                    }

                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    fileInput.files = transfer.files;
                    setFileState(file);
                };

                pickButton.addEventListener('click', function () {
                    fileInput.click();
                });

                dropzone.addEventListener('click', function (event) {
                    if (event.target.closest('button')) return;
                    fileInput.click();
                });

                dropzone.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        fileInput.click();
                    }
                });

                fileInput.addEventListener('change', function () {
                    setFileState(fileInput.files[0] || null);
                });

                ['dragenter', 'dragover'].forEach(function (eventName) {
                    dropzone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        event.stopPropagation();
                        dropzone.classList.add('is-dragover');
                    });
                });

                ['dragleave', 'dragend'].forEach(function (eventName) {
                    dropzone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        event.stopPropagation();
                        dropzone.classList.remove('is-dragover');
                    });
                });

                dropzone.addEventListener('drop', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dropzone.classList.remove('is-dragover');

                    const file = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files[0] : null;
                    if (!file) return;

                    assignDroppedFile(file);
                });

                form.addEventListener('submit', function (event) {
                    const file = fileInput.files[0] || null;
                    setFileState(file);

                    if (!file || submitButton.disabled) {
                        event.preventDefault();
                        return;
                    }

                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Importing...';
                });
            }
        }

        const detailButtons = document.querySelectorAll('.js-view-import-details');
        if (detailButtons.length === 0) return;

        const numberFormatter = new Intl.NumberFormat();
        const detailMeta = document.getElementById('importBatchDetailMeta');
        const detailTotal = document.getElementById('importBatchDetailTotal');
        const detailSuccess = document.getElementById('importBatchDetailSuccess');
        const detailFailed = document.getElementById('importBatchDetailFailed');
        const detailStatus = document.getElementById('importBatchDetailStatus');
        const detailRate = document.getElementById('importBatchDetailRate');
        const detailRowsBody = document.getElementById('importBatchDetailRows');
        const noErrorsAlert = document.getElementById('importBatchDetailNoErrors');
        const statusClasses = ['is-empty', 'is-success', 'is-failed', 'is-partial'];

        if (!detailMeta || !detailTotal || !detailSuccess || !detailFailed || !detailStatus || !detailRate || !detailRowsBody || !noErrorsAlert) {
            return;
        }

        const createCell = function (value) {
            const cell = document.createElement('td');
            cell.textContent = value;
            return cell;
        };

        const parseFailedRows = function (batchId) {
            const source = document.getElementById('batch-failed-rows-' + batchId);
            if (!source) return [];

            const payload = source.textContent ? source.textContent.trim() : '';
            if (!payload) return [];

            try {
                const parsed = JSON.parse(payload);
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        };

        detailButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const batchId = this.dataset.batchId || '';
                const batchFile = this.dataset.batchFile || '-';
                const importedBy = this.dataset.importedBy || '-';
                const importedAt = this.dataset.importedAt || '-';
                const total = Number(this.dataset.total || 0);
                const success = Number(this.dataset.success || 0);
                const failed = Number(this.dataset.failed || 0);
                const successRate = Number(this.dataset.successRate || 0);
                const statusLabel = this.dataset.statusLabel || '-';
                const statusClass = this.dataset.statusClass || 'is-empty';
                const failedRows = parseFailedRows(batchId);

                detailMeta.textContent = batchFile + ' | Batch #' + batchId + ' | Imported by ' + importedBy + ' at ' + importedAt;
                detailTotal.textContent = numberFormatter.format(total);
                detailSuccess.textContent = numberFormatter.format(success);
                detailFailed.textContent = numberFormatter.format(failed);
                detailRate.textContent = successRate.toFixed(1) + '% success rate';
                detailStatus.textContent = statusLabel;
                statusClasses.forEach(function (className) {
                    detailStatus.classList.remove(className);
                });
                detailStatus.classList.add(statusClass);

                detailRowsBody.innerHTML = '';
                if (failedRows.length === 0) {
                    noErrorsAlert.classList.remove('d-none');
                    const emptyRow = document.createElement('tr');
                    const emptyCell = document.createElement('td');
                    emptyCell.colSpan = 5;
                    emptyCell.className = 'text-center text-secondary py-4';
                    emptyCell.textContent = 'No failed rows in this batch.';
                    emptyRow.appendChild(emptyCell);
                    detailRowsBody.appendChild(emptyRow);
                    return;
                }

                noErrorsAlert.classList.add('d-none');
                failedRows.forEach(function (row) {
                    const tr = document.createElement('tr');
                    tr.appendChild(createCell(String(row.row_number || '-')));
                    tr.appendChild(createCell(row.title || '-'));
                    tr.appendChild(createCell(row.division || '-'));
                    tr.appendChild(createCell(row.department || '-'));
                    tr.appendChild(createCell(row.error_message || 'Unknown error.'));
                    detailRowsBody.appendChild(tr);
                });
            });
        });
    })();
</script>
@endsection
