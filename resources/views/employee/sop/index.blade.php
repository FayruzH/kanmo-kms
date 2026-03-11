@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-3">SOP Dashboard</h1>

    <form method="GET" action="{{ route('employee.sop.index') }}" class="row g-2 mb-3">
        <div class="col-md-10">
            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search SOP">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    <form method="POST" action="{{ route('employee.sop.ask') }}" class="card card-body mb-3">
        @csrf
        <label class="form-label fw-semibold">AI Search (ChatGPT style)</label>
        <div class="d-flex gap-2">
            <input type="text" name="q" class="form-control" placeholder="Contoh: SOP untuk annual leave?" required>
            <button class="btn btn-outline-primary" type="submit">Ask</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Expiry</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->category?->name }}</td>
                        <td>{{ $item->department?->name }}</td>
                        <td>
                            <span class="badge text-bg-{{ $item->status === 'expired' ? 'danger' : ($item->status === 'expiring_soon' ? 'warning' : 'success') }}">
                                {{ $item->status }}
                            </span>
                        </td>
                        <td>{{ optional($item->expiry_date)->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('employee.sop.show', $item) }}" class="btn btn-sm btn-primary">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No SOP found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}
</div>
@endsection
