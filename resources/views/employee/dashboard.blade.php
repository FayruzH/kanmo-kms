@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <section class="kms-hero mb-4">
        <h2>Knowledge Management System</h2>
        <p class="fs-4 mb-0 opacity-75">
            Find, access, and manage all Standard Operating Procedures in one place.
            Use AI-powered search for instant answers.
        </p>
        <form method="GET" action="{{ route('employee.dashboard') }}" class="kms-searchbox">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search SOPs by title, tag, or keyword..." value="{{ request('search') }}">
                <button class="btn btn-primary rounded-3 px-3" type="submit">Search</button>
            </div>
        </form>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3"><div class="kms-stat"><div class="d-flex justify-content-between"><div class="kms-stat-title">Total SOPs</div><i class="bi bi-file-earmark-text fs-4 text-info"></i></div><div class="kms-stat-value">{{ $totals['all'] }}</div></div></div>
        <div class="col-md-6 col-xl-3"><div class="kms-stat"><div class="d-flex justify-content-between"><div class="kms-stat-title">Active</div><i class="bi bi-check-circle fs-4 text-success"></i></div><div class="kms-stat-value">{{ $totals['active'] }}</div></div></div>
        <div class="col-md-6 col-xl-3"><div class="kms-stat"><div class="d-flex justify-content-between"><div class="kms-stat-title">Expiring Soon</div><i class="bi bi-clock fs-4 text-warning"></i></div><div class="kms-stat-value">{{ $totals['expiring_soon'] }}</div></div></div>
        <div class="col-md-6 col-xl-3"><div class="kms-stat"><div class="d-flex justify-content-between"><div class="kms-stat-title">Expired</div><i class="bi bi-exclamation-triangle fs-4 text-danger"></i></div><div class="kms-stat-value">{{ $totals['expired'] }}</div></div></div>
    </div>

    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
        <form method="GET" action="{{ route('employee.dashboard') }}" class="row g-2 flex-grow-1">
            <div class="col-md-3">
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach (['active','expiring_soon','expired'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
            </div>
        </form>
        <div class="small text-secondary">{{ number_format($items->total()) }} results</div>
    </div>

    <div class="row g-3">
        @forelse ($items as $item)
            <div class="col-12 col-lg-6 col-xl-4">
                <article class="kms-card h-100 d-flex flex-column position-relative">
                    <a href="{{ route('employee.sop.show', $item) }}" class="stretched-link" aria-label="Open {{ $item->title }}"></a>
                    @php
                        $daysLeft = $item->expiry_date ? now()->startOfDay()->diffInDays($item->expiry_date->startOfDay(), false) : null;
                        $timeLeftLabel = '-';
                        if ($daysLeft !== null) {
                            if ($daysLeft < 0) {
                                $timeLeftLabel = abs($daysLeft) . 'd overdue';
                            } elseif ($daysLeft >= 365) {
                                $years = (int) floor($daysLeft / 365);
                                $timeLeftLabel = $years . ' ' . ($years === 1 ? 'Year' : 'Years') . ' left';
                            } elseif ($daysLeft >= 30) {
                                $months = (int) floor($daysLeft / 30);
                                $timeLeftLabel = $months . ' ' . ($months === 1 ? 'Month' : 'Months') . ' left';
                            } else {
                                $timeLeftLabel = $daysLeft . ' ' . ($daysLeft === 1 ? 'Day' : 'Days') . ' left';
                            }
                        }
                    @endphp
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="kms-type-icon {{ $item->type === 'url' ? 'url' : 'file' }}">
                                <i class="bi {{ $item->type === 'url' ? 'bi-box-arrow-up-right' : 'bi-file-earmark-text' }} fs-4"></i>
                            </span>
                            <div>
                                <h3 class="h5 mb-0">{{ \Illuminate\Support\Str::limit($item->title, 40) }}</h3>
                                <div class="text-secondary small">{{ $item->department?->name }} · {{ $item->category?->name }} · {{ $item->version ?: 'v1.0' }}</div>
                            </div>
                        </div>
                        <span class="status-pill status-{{ $item->status }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span>
                    </div>
                    <p class="text-secondary mb-3 flex-grow-1">{{ \Illuminate\Support\Str::limit($item->summary ?: 'No summary available.', 130) }}</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @forelse($item->tags->take(4) as $tag)
                            <span class="kms-chip">{{ $tag->name }}</span>
                        @empty
                            <span class="kms-chip">untagged</span>
                        @endforelse
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('employee.sop.show', $item) }}" class="btn btn-sm btn-outline-primary position-relative" style="z-index:2;">View</a>
                        <form
                            method="POST"
                            action="{{ $item->type === 'file' ? route('employee.sop.download', $item) : route('employee.sop.open', $item) }}"
                            class="position-relative"
                            style="z-index:2;"
                        >
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary">
                                {{ $item->type === 'file' ? 'Download' : 'Open' }}
                            </button>
                        </form>
                    </div>

                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center small text-secondary">
                        <div class="d-flex align-items-center gap-3">
                            <span><i class="bi bi-eye me-1"></i>{{ $item->views_count }}</span>
                            <span><i class="bi bi-heart me-1"></i>{{ $item->likes_count }}</span>
                            <span><i class="bi bi-chat me-1"></i>{{ $item->comments_count }}</span>
                        </div>
                        <span><i class="bi bi-calendar4-event me-1"></i>{{ $timeLeftLabel }}</span>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-light border">No SOP found.</div></div>
        @endforelse
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection
