@extends('layouts.app')

@section('page_title', 'Bulk Import')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Bulk Import SOPs</h2>
        <p class="text-secondary mb-0">Upload an Excel or CSV file to create multiple SOP entries at once.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="kms-table-wrap p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-semibold mb-1"><i class="bi bi-file-earmark-arrow-down text-success me-2"></i>Download Import Template</h4>
                <div class="text-secondary">Use this template to ensure correct column mapping. Required: Title, Category, Department, Expiry Date, PIC.</div>
            </div>
            <a href="{{ route('admin.sop.import.template') }}" class="btn btn-outline-secondary px-4">Template</a>
        </div>
    </div>

    <div class="kms-upload-drop mb-4">
        <form action="{{ route('admin.sop.import.store') }}" method="POST" enctype="multipart/form-data" class="text-center">
            @csrf
            <i class="bi bi-upload display-6 text-secondary"></i>
            <h4 class="fw-semibold mt-3">Drag & drop your file here</h4>
            <p class="text-secondary">Supports .xlsx, .xls, .csv files</p>
            <label class="btn btn-outline-secondary">
                Browse Files
                <input type="file" name="file" class="d-none" accept=".csv,.xls,.xlsx" required>
            </label>
            <div class="mt-3">
                <button class="btn btn-primary px-4">Start Import</button>
            </div>
        </form>
    </div>

    <h3 class="h4 fw-bold mb-3">Import History</h3>
    <div class="kms-table-wrap">
        <table class="table kms-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Imported By</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Success</th>
                    <th>Failed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>{{ $batch->filename }}</td>
                        <td>{{ $batch->admin?->name }}</td>
                        <td>{{ $batch->created_at?->format('M j, Y') }}</td>
                        <td>{{ $batch->totals_json['total'] ?? 0 }}</td>
                        <td class="text-success">{{ $batch->totals_json['success'] ?? 0 }}</td>
                        <td class="text-danger">{{ $batch->totals_json['failed'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-secondary">No import history.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $batches->links() }}</div>
</div>
@endsection
