@extends('layouts.app')

@section('page_title', 'AI Search')

@section('content')
<div class="container-fluid px-0">
    <div class="kms-ai-center">
        <div class="kms-ai-icon"><i class="bi bi-stars"></i></div>
        <h2 class="fw-bold mb-2">AI-Powered SOP Search</h2>
        <p class="text-secondary mb-4">Ask any question about company procedures. Get instant answers with citations from official SOPs.</p>

        <form method="POST" action="{{ route('employee.ai.ask') }}" class="w-100" style="max-width: 780px;">
            @csrf
            <div class="input-group kms-search-line">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Ask a question, e.g. 'How do I request annual leave?'" value="{{ $query ?? '' }}" required>
                <button class="btn btn-primary px-4">Ask AI</button>
            </div>
        </form>

        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
            <span class="kms-chip">How to request annual leave?</span>
            <span class="kms-chip">Store opening checklist procedure</span>
            <span class="kms-chip">What is the expense reimbursement policy?</span>
            <span class="kms-chip">Fire safety and evacuation steps</span>
        </div>
    </div>

    @if(!empty($answer))
        <div class="kms-table-wrap p-4 mt-4">
            <h4 class="fw-semibold">Answer</h4>
            <pre class="mb-0" style="white-space: pre-wrap;">{{ $answer }}</pre>
        </div>
    @endif

    @if(!empty($items))
        <div class="kms-table-wrap p-4 mt-3">
            <h4 class="fw-semibold">Top SOP Matches</h4>
            <ul class="mb-0">
                @foreach($items as $item)
                    <li class="mb-2">
                        <a href="{{ route('employee.sop.show', $item) }}" class="fw-semibold text-decoration-none">{{ $item->title }}</a>
                        <span class="text-secondary">({{ $item->category?->name }} / {{ $item->department?->name }})</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
