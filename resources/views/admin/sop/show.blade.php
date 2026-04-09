@extends('layouts.app')

@section('page_title', 'SOP Detail')

@section('content')
<div class="container-fluid px-0">
    <a href="{{ route('admin.overview') }}" class="text-decoration-none text-secondary small mb-3 d-inline-block">
        <i class="bi bi-arrow-left me-1"></i> Back to Overview
    </a>

    <div class="kms-detail-card mb-3">
        <div class="kms-detail-hero">
            <div class="kms-detail-meta-line">
                <span class="small opacity-75">SOP-{{ str_pad((string) $sop->id, 3, '0', STR_PAD_LEFT) }}</span>
                <span class="status-pill status-{{ $sop->status }}">{{ ucfirst(str_replace('_', ' ', $sop->status)) }}</span>
            </div>
            <h1 class="h2 fw-bold mt-2 mb-2">{{ $sop->title }}</h1>
            <p class="mb-0 opacity-75">{{ $sop->summary ?: 'No summary available.' }}</p>
        </div>

        <div class="p-4 kms-detail-body">
            <div class="kms-detail-grid mb-3">
                <div class="kms-info-box"><div class="kms-info-label">Division</div><div class="kms-info-value">{{ $sop->category?->name ?: '-' }}</div></div>
                <div class="kms-info-box"><div class="kms-info-label">Department</div><div class="kms-info-value">{{ $sop->department?->name ?: '-' }}</div></div>
                <div class="kms-info-box"><div class="kms-info-label">Version</div><div class="kms-info-value">{{ $sop->version ?: '-' }}</div></div>
                <div class="kms-info-box"><div class="kms-info-label">PIC (Owner)</div><div class="kms-info-value">{{ $sop->pic?->name ?: '-' }}</div></div>
                <div class="kms-info-box"><div class="kms-info-label">Effective Date</div><div class="kms-info-value">{{ optional($sop->effective_date)->format('M j, Y') ?: '-' }}</div></div>
                <div class="kms-info-box"><div class="kms-info-label">Expiry Date</div><div class="kms-info-value">{{ optional($sop->expiry_date)->format('M j, Y') ?: '-' }}</div></div>
                <div class="kms-info-box"><div class="kms-info-label">Source</div><div class="kms-info-value">{{ $sop->source_name ?: ($sop->type === 'url' ? 'External URL' : 'Uploaded File') }}</div></div>
                <div class="kms-info-box"><div class="kms-info-label">Entity</div><div class="kms-info-value">{{ $sop->entity ?: '-' }}</div></div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-3 kms-detail-tags">
                @forelse($sop->tags as $tag)
                    <span class="kms-chip">{{ $tag->name }}</span>
                @empty
                    <span class="kms-chip">untagged</span>
                @endforelse
            </div>

            <hr class="kms-detail-divider">

            <div class="kms-detail-actions mb-3">
                <a href="{{ route('admin.sop.edit', $sop) }}" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i>Edit SOP</a>
                <form method="POST" action="{{ $sop->type === 'file' ? route('employee.sop.download', $sop) : route('employee.sop.open', $sop) }}">
                    @csrf
                    <button class="btn btn-outline-secondary">
                        <i class="bi {{ $sop->type === 'file' ? 'bi-download' : 'bi-box-arrow-up-right' }} me-1"></i>
                        {{ $sop->type === 'file' ? 'Download SOP' : 'Open SOP' }}
                    </button>
                </form>
            </div>

            <div class="small text-secondary kms-detail-stats">
                <span><i class="bi bi-eye me-1"></i>{{ $sop->views_count }} views</span>
                <span><i class="bi bi-heart me-1"></i>{{ $sop->likes_count }} likes</span>
                <span><i class="bi bi-chat me-1"></i>{{ $sop->comments_count }} comments</span>
            </div>
        </div>
    </div>

    <div class="kms-table-wrap p-4">
        <h3 class="h5 fw-bold mb-3">Comments ({{ $sop->comments_count }})</h3>
        <div class="d-grid gap-2 kms-comments-list">
            @forelse($sop->comments as $comment)
                <div class="p-3 rounded-3 border kms-comment-item">
                    <div class="small text-secondary mb-1">{{ $comment->user?->name }} · {{ $comment->created_at?->format('M j, Y') }}</div>
                    <div>{{ $comment->comment_text }}</div>
                </div>
            @empty
                <div class="text-secondary">No comments yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
