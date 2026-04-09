@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <section class="kms-hero mb-4">
        <h2>Knowledge Management System</h2>
        <p class="fs-4 mb-0 opacity-75">
            Find, access, and manage all Standard Operating Procedures in one place.
        </p>
        <form method="GET" action="{{ route('employee.dashboard') }}" class="kms-searchbox" data-auto-submit>
            <div class="input-group">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search SOPs by title, tag, or keyword..." value="{{ request('search') }}" data-auto-submit-input>
                <button class="btn btn-primary rounded-3 px-3" type="submit">Search</button>
            </div>
        </form>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="kms-stat kms-stat-clickable" role="button" tabindex="0" data-stat-key="all" data-stat-label="Total SOPs">
                <div class="d-flex justify-content-between">
                    <div class="kms-stat-title">Total SOPs</div>
                    <i class="bi bi-file-earmark-text fs-4 text-info"></i>
                </div>
                <div class="kms-stat-value">{{ $totals['all'] }}</div>
                <div class="small text-secondary">Click for details</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="kms-stat kms-stat-clickable" role="button" tabindex="0" data-stat-key="active" data-stat-label="Active">
                <div class="d-flex justify-content-between">
                    <div class="kms-stat-title">Active</div>
                    <i class="bi bi-check-circle fs-4 text-success"></i>
                </div>
                <div class="kms-stat-value">{{ $totals['active'] }}</div>
                <div class="small text-secondary">Click for details</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="kms-stat kms-stat-clickable" role="button" tabindex="0" data-stat-key="expiring_soon" data-stat-label="Expiring Soon">
                <div class="d-flex justify-content-between">
                    <div class="kms-stat-title">Expiring Soon</div>
                    <i class="bi bi-clock fs-4 text-warning"></i>
                </div>
                <div class="kms-stat-value">{{ $totals['expiring_soon'] }}</div>
                <div class="small text-secondary">Click for details</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="kms-stat kms-stat-clickable" role="button" tabindex="0" data-stat-key="expired" data-stat-label="Expired">
                <div class="d-flex justify-content-between">
                    <div class="kms-stat-title">Expired</div>
                    <i class="bi bi-exclamation-triangle fs-4 text-danger"></i>
                </div>
                <div class="kms-stat-value">{{ $totals['expired'] }}</div>
                <div class="small text-secondary">Click for details</div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
        <form method="GET" action="{{ route('employee.dashboard') }}" class="row g-2 flex-grow-1" data-auto-submit>
            <div class="col-md-4">
                <select name="category_id" class="form-select" data-auto-submit-select>
                    <option value="">All Divisions</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select name="department_id" class="form-select" data-auto-submit-select>
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select" data-auto-submit-select>
                    <option value="">All Status</option>
                    @foreach (['active','expiring_soon','expired'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
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
                                <div class="text-secondary small">{{ $item->category?->name }} | {{ $item->department?->name }} | {{ $item->version ?: 'v1.0' }}</div>
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

<div class="modal fade" id="statDetailModal" tabindex="-1" aria-labelledby="statDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="statDetailModalLabel">Detail SOP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="statDetailLoading" class="p-4 text-center text-secondary small d-none">
                    Loading SOP details...
                </div>
                <div id="statDetailEmpty" class="p-4 text-center text-secondary small d-none">
                    No SOP data found for this filter.
                </div>
                <div id="statDetailError" class="p-4 text-center text-danger small d-none">
                    Failed to load SOP details. Please try again.
                </div>
                <div id="statDetailTableWrap" class="table-responsive d-none">
                    <table class="table kms-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>SOP ID</th>
                                <th>Title</th>
                                <th>Division</th>
                                <th>Department</th>
                                <th>PIC</th>
                                <th>Status</th>
                                <th>Expiry Date</th>
                                <th>Last Updated</th>
                                <th style="width:90px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="statDetailBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div id="statDetailMeta" class="small text-secondary">-</div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" id="statDetailPrev">Prev</button>
                    <button type="button" class="btn btn-outline-secondary" id="statDetailNext">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const endpoint = @json(route('employee.dashboard.stats-detail'));
        const cards = Array.from(document.querySelectorAll('.kms-stat-clickable[data-stat-key]'));
        const modalElement = document.getElementById('statDetailModal');
        if (!modalElement || cards.length === 0 || typeof bootstrap === 'undefined') {
            return;
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const titleElement = document.getElementById('statDetailModalLabel');
        const loadingElement = document.getElementById('statDetailLoading');
        const emptyElement = document.getElementById('statDetailEmpty');
        const errorElement = document.getElementById('statDetailError');
        const tableWrapElement = document.getElementById('statDetailTableWrap');
        const bodyElement = document.getElementById('statDetailBody');
        const metaElement = document.getElementById('statDetailMeta');
        const prevButton = document.getElementById('statDetailPrev');
        const nextButton = document.getElementById('statDetailNext');

        const statusClassMap = {
            active: 'status-active',
            expiring_soon: 'status-expiring_soon',
            expired: 'status-expired',
            archived: 'status-archived',
        };

        const state = {
            statKey: 'all',
            statLabel: 'Total SOPs',
            currentPage: 1,
            lastPage: 1,
        };

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function hideFeedback() {
            loadingElement.classList.add('d-none');
            emptyElement.classList.add('d-none');
            errorElement.classList.add('d-none');
        }

        function setLoadingState() {
            hideFeedback();
            loadingElement.classList.remove('d-none');
            tableWrapElement.classList.add('d-none');
            bodyElement.innerHTML = '';
            metaElement.textContent = 'Loading...';
            prevButton.disabled = true;
            nextButton.disabled = true;
        }

        function setErrorState(message) {
            hideFeedback();
            errorElement.textContent = message;
            errorElement.classList.remove('d-none');
            tableWrapElement.classList.add('d-none');
            bodyElement.innerHTML = '';
            metaElement.textContent = 'Failed to load data';
            prevButton.disabled = true;
            nextButton.disabled = true;
        }

        function setEmptyState() {
            hideFeedback();
            emptyElement.classList.remove('d-none');
            tableWrapElement.classList.add('d-none');
            bodyElement.innerHTML = '';
        }

        function renderRows(rows) {
            if (!Array.isArray(rows) || rows.length === 0) {
                setEmptyState();
                return;
            }

            hideFeedback();
            tableWrapElement.classList.remove('d-none');

            bodyElement.innerHTML = rows.map(function (row) {
                const statusClass = statusClassMap[row.status] || 'status-archived';
                return '<tr>'
                    + '<td class="small text-secondary">' + escapeHtml(row.sop_code) + '</td>'
                    + '<td class="fw-semibold">' + escapeHtml(row.title) + '</td>'
                    + '<td>' + escapeHtml(row.division) + '</td>'
                    + '<td>' + escapeHtml(row.department) + '</td>'
                    + '<td>' + escapeHtml(row.pic) + '</td>'
                    + '<td><span class="status-pill ' + statusClass + '">' + escapeHtml(row.status_label) + '</span></td>'
                    + '<td>' + escapeHtml(row.expiry_date_label || '-') + '</td>'
                    + '<td class="small text-secondary">' + escapeHtml(row.updated_at_label || '-') + '</td>'
                    + '<td><a href="' + escapeHtml(row.detail_url || '#') + '" class="btn btn-sm btn-light border">Open</a></td>'
                    + '</tr>';
            }).join('');
        }

        function updatePagination(meta) {
            state.currentPage = Number(meta.current_page || 1);
            state.lastPage = Number(meta.last_page || 1);

            const from = meta.from || 0;
            const to = meta.to || 0;
            const total = meta.total || 0;
            metaElement.textContent = total > 0
                ? 'Showing ' + from + '-' + to + ' of ' + total + ' SOP'
                : 'No data';

            prevButton.disabled = state.currentPage <= 1;
            nextButton.disabled = state.currentPage >= state.lastPage;
        }

        async function loadPage(page) {
            setLoadingState();

            try {
                const params = new URLSearchParams(window.location.search);
                params.set('stat', state.statKey);
                params.set('page', String(page));
                params.set('per_page', '10');

                const response = await fetch(endpoint + '?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const payload = await response.json();
                renderRows(payload.data || []);
                updatePagination(payload.meta || {});
            } catch (error) {
                setErrorState('Failed to load SOP details. Please try again.');
            }
        }

        function openModalForCard(card) {
            state.statKey = card.dataset.statKey || 'all';
            state.statLabel = card.dataset.statLabel || 'SOP';
            titleElement.textContent = 'Detail SOP - ' + state.statLabel;
            modal.show();
            loadPage(1);
        }

        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                openModalForCard(card);
            });

            card.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                event.preventDefault();
                openModalForCard(card);
            });
        });

        prevButton.addEventListener('click', function () {
            if (state.currentPage <= 1) {
                return;
            }

            loadPage(state.currentPage - 1);
        });

        nextButton.addEventListener('click', function () {
            if (state.currentPage >= state.lastPage) {
                return;
            }

            loadPage(state.currentPage + 1);
        });
    })();
</script>
@endsection
