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

    <form method="POST" action="{{ route('admin.settings.update') }}" class="kms-table-wrap p-4" style="max-width: 900px;">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="form-label fw-semibold">Expiry Threshold (days)</label>
            <div class="text-secondary mb-2">SOPs expiring within this many days will show as "Expiring Soon".</div>
            <input type="number" min="1" max="365" class="form-control" style="max-width: 360px;" name="expiry_threshold_days" value="{{ old('expiry_threshold_days', $expiryThreshold) }}">
        </div>

        <div class="mb-4">
            <h4 class="h5 fw-semibold mb-1">Categories</h4>
            <div class="text-secondary mb-2">Configured categories for SOP classification.</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($categories as $name)
                    <span class="kms-chip">{{ $name }}</span>
                @endforeach
            </div>
        </div>

        <div class="mb-4">
            <h4 class="h5 fw-semibold mb-1">Departments</h4>
            <div class="text-secondary mb-2">Configured departments.</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($departments as $name)
                    <span class="kms-chip">{{ $name }}</span>
                @endforeach
            </div>
        </div>

        <div class="mb-4">
            <h4 class="h5 fw-semibold mb-1">Source Apps</h4>
            <div class="text-secondary mb-2">External applications that can be linked as SOP sources.</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($sourceApps as $name)
                    <span class="kms-chip">{{ $name }}</span>
                @endforeach
            </div>
        </div>

        <button class="btn btn-primary px-4"><i class="bi bi-floppy me-1"></i>Save Settings</button>
    </form>
</div>
@endsection
