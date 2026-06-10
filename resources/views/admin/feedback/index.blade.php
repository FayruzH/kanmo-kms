@extends('layouts.app')

@section('page_title', 'Feedback Chatbot')

@section('content')
<div class="container-fluid px-0">
    @if(!empty($loadError))
        <div class="alert alert-warning mb-3">{{ $loadError }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <form method="GET" action="{{ route('admin.feedback.index') }}" class="d-flex flex-grow-1 gap-2">
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Search question, detail, name, email, or NIP..."
                value="{{ request('search') }}"
            >
            <button type="submit" class="btn btn-primary px-4">Search</button>
            @if(request()->filled('search'))
                <a href="{{ route('admin.feedback.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </form>
        <div class="small text-secondary">{{ number_format($items->total()) }} feedback</div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 190px;">Waktu</th>
                        <th style="width: 240px;">Pengirim</th>
                        <th style="min-width: 340px;">Pertanyaan</th>
                        <th style="min-width: 340px;">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $senderName = $item->user?->name ?? $item->user_name ?? '-';
                            $senderEmail = $item->user?->email ?? $item->user_email ?? '-';
                        @endphp
                        <tr>
                            <td class="small text-secondary">
                                {{ optional($item->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $senderName }}</div>
                                <div class="small text-secondary">{{ $senderEmail }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $item->question }}</div>
                            </td>
                            <td class="text-secondary">
                                {{ $item->detail ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-secondary">
                                Belum ada feedback chatbot.
                            </td>
                        </tr>
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
