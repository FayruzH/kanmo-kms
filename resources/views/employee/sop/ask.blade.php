@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-3">AI Search Result</h1>
    <p><strong>Query:</strong> {{ $query }}</p>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h5">Answer</h2>
            <pre class="mb-0" style="white-space: pre-wrap;">{{ $answer }}</pre>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h5">Top SOP Matches</h2>
            <ul class="mb-0">
                @forelse($items as $item)
                    <li>
                        <a href="{{ route('employee.sop.show', $item) }}">{{ $item->title }}</a>
                        <span class="text-muted">({{ $item->category?->name }} / {{ $item->department?->name }})</span>
                    </li>
                @empty
                    <li>No match found.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
