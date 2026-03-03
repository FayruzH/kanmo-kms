@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">SOP Management</h1>
    <a class="btn btn-primary" href="{{ route('admin.sop.create') }}">+ Add SOP</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead>
          <tr>
            <th>Title</th><th>Dept</th><th>Category</th><th>Status</th><th>Expiry</th><th>PIC</th><th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $item)
            <tr>
              <td class="fw-semibold">{{ $item->title }}</td>
              <td>{{ $item->department->name }}</td>
              <td>{{ $item->category->name }}</td>
              <td><span class="badge text-bg-secondary">{{ $item->status }}</span></td>
              <td>{{ optional($item->expiry_date)->format('Y-m-d') }}</td>
              <td>{{ $item->pic->name }}</td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.sop.edit', $item) }}">Edit</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No SOP yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $items->links() }}
  </div>
</div>
@endsection
