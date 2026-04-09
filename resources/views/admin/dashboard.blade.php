@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
<div class="container-fluid px-0">
    <section class="kms-hero mb-4">
        <h2>Knowledge Management System</h2>
        <p class="fs-4 mb-0 opacity-75">
            Find, access, and manage all Standard Operating Procedures in one place.
        </p>
        <form method="GET" action="{{ route('admin.overview') }}" class="kms-searchbox" data-auto-submit>
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
        <form method="GET" action="{{ route('admin.overview') }}" class="row g-2 flex-grow-1" data-auto-submit>
            <div class="col-md-3">
                <select name="category_id" class="form-select" data-auto-submit-select>
                    <option value="">All Divisions</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="department_id" class="form-select" data-auto-submit-select>
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" data-auto-submit-select>
                    <option value="">All Status</option>
                    @foreach (['active','expiring_soon','expired','archived'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2 h-100">
                    <a href="{{ route('admin.sop.create') }}" class="btn btn-primary flex-grow-1">Create SOP</a>
                    <a href="{{ route('admin.overview') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
        <div class="small text-secondary">{{ number_format($items->total()) }} results</div>
    </div>

    <div class="row g-3">
        @forelse ($items as $item)
            <div class="col-12 col-lg-6 col-xl-4">
                <article class="kms-card h-100 d-flex flex-column position-relative">
                    <a href="{{ route('admin.sop.show', $item) }}" class="stretched-link" aria-label="Open {{ $item->title }}"></a>
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
                <div id="statDetailContext" class="kms-detail-context is-all">
                    <span class="kms-detail-context-icon">
                        <i id="statDetailContextIcon" class="bi bi-file-earmark-text"></i>
                    </span>
                    <div class="d-flex flex-column">
                        <span id="statDetailContextLabel" class="kms-detail-context-label">Total SOPs</span>
                        <span class="kms-detail-context-value"><span id="statDetailContextCount">0</span> SOP</span>
                    </div>
                </div>
                <div id="statDetailSummaryWrap" class="p-3 border-bottom d-none">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <div class="small fw-semibold text-uppercase text-secondary mb-2">SOP per Division</div>
                            <div id="statDetailDivisionCards" class="d-grid gap-2"></div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="small fw-semibold text-uppercase text-secondary mb-2">SOP per Department</div>
                            <div id="statDetailDepartmentCards" class="d-grid gap-2"></div>
                        </div>
                    </div>
                </div>
                <div id="statDetailLoading" class="p-4 text-center text-secondary small d-none">
                    Loading SOP details...
                </div>
                <div id="statDetailEmpty" class="p-4 text-center text-secondary small d-none">
                    No SOP data found for this filter.
                </div>
                <div id="statDetailError" class="p-4 text-center text-danger small d-none">
                    Failed to load SOP details. Please try again.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const bootstrapApi = window.bootstrap;
        const endpoint = @json(route('admin.overview.stats-detail'));
        const cards = Array.from(document.querySelectorAll('.kms-stat-clickable[data-stat-key]'));
        const modalElement = document.getElementById('statDetailModal');
        if (!modalElement || cards.length === 0 || !bootstrapApi || !bootstrapApi.Modal) {
            return;
        }

        const modal = bootstrapApi.Modal.getOrCreateInstance(modalElement);
        const titleElement = document.getElementById('statDetailModalLabel');
        const loadingElement = document.getElementById('statDetailLoading');
        const emptyElement = document.getElementById('statDetailEmpty');
        const errorElement = document.getElementById('statDetailError');
        const contextElement = document.getElementById('statDetailContext');
        const contextLabelElement = document.getElementById('statDetailContextLabel');
        const contextCountElement = document.getElementById('statDetailContextCount');
        const contextIconElement = document.getElementById('statDetailContextIcon');
        const summaryWrapElement = document.getElementById('statDetailSummaryWrap');
        const divisionCardsElement = document.getElementById('statDetailDivisionCards');
        const departmentCardsElement = document.getElementById('statDetailDepartmentCards');

        const state = {
            statKey: 'all',
            statLabel: 'Total SOPs',
        };
        const contextClassMap = {
            all: 'is-all',
            active: 'is-active',
            expiring_soon: 'is-expiring',
            expired: 'is-expired',
        };
        const contextIconMap = {
            all: 'bi-file-earmark-text',
            active: 'bi-check-circle',
            expiring_soon: 'bi-clock',
            expired: 'bi-exclamation-triangle',
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

        function formatCount(value) {
            const parsed = Number(value || 0);
            if (!Number.isFinite(parsed)) {
                return '0';
            }

            return parsed.toLocaleString('id-ID');
        }

        function setContext(card) {
            if (!card) {
                return;
            }

            const statKey = card.dataset.statKey || 'all';
            const statLabel = card.dataset.statLabel || 'SOP';
            const statCount = card.querySelector('.kms-stat-value')?.textContent?.trim() || '0';

            state.statKey = statKey;
            state.statLabel = statLabel;
            titleElement.textContent = 'Detail SOP - ' + state.statLabel;

            if (contextLabelElement) {
                contextLabelElement.textContent = statLabel;
            }
            if (contextCountElement) {
                contextCountElement.textContent = statCount;
            }
            if (contextIconElement) {
                contextIconElement.className = 'bi ' + (contextIconMap[statKey] || contextIconMap.all);
            }
            if (contextElement) {
                Object.values(contextClassMap).forEach(function (className) {
                    contextElement.classList.remove(className);
                });
                contextElement.classList.add(contextClassMap[statKey] || contextClassMap.all);
            }

            cards.forEach(function (item) {
                item.classList.remove('kms-stat-selected');
            });
            card.classList.add('kms-stat-selected');
        }

        function clearSummary() {
            if (!summaryWrapElement) {
                return;
            }

            summaryWrapElement.classList.add('d-none');
            if (divisionCardsElement) {
                divisionCardsElement.innerHTML = '';
            }
            if (departmentCardsElement) {
                departmentCardsElement.innerHTML = '';
            }
        }

        function renderSummaryCards(target, rows, resolveLabel, filterKey) {
            if (!target) {
                return;
            }

            if (!Array.isArray(rows) || rows.length === 0) {
                target.innerHTML = '<div class="small text-secondary">No data</div>';
                return;
            }

            target.innerHTML = rows.map(function (row) {
                const filterValue = row && row.id !== null && row.id !== undefined ? String(row.id) : '';
                if (filterValue !== '' && filterKey) {
                    return '<button type="button" class="kms-summary-item kms-summary-item-clickable border rounded-3 px-3 py-2 text-start w-100" data-dashboard-filter-key="' + escapeHtml(filterKey) + '" data-dashboard-filter-value="' + escapeHtml(filterValue) + '">'
                        + '<span class="small fw-semibold kms-summary-label">' + escapeHtml(resolveLabel(row)) + '</span>'
                        + '<span class="badge text-bg-light border kms-summary-count">' + escapeHtml(formatCount(row.total)) + '</span>'
                        + '</button>';
                }

                return '<div class="kms-summary-item border rounded-3 px-3 py-2">'
                    + '<span class="small fw-semibold kms-summary-label">' + escapeHtml(resolveLabel(row)) + '</span>'
                    + '<span class="badge text-bg-light border kms-summary-count">' + escapeHtml(formatCount(row.total)) + '</span>'
                    + '</div>';
            }).join('');
        }

        function renderSummaries(summaries) {
            if (!summaryWrapElement) {
                return;
            }

            const byDivision = Array.isArray(summaries.by_division) ? summaries.by_division : [];
            const byDepartment = Array.isArray(summaries.by_department) ? summaries.by_department : [];
            const hasAnySummary = byDivision.length > 0 || byDepartment.length > 0;

            if (!hasAnySummary) {
                setEmptyState();
                return;
            }

            hideFeedback();
            summaryWrapElement.classList.remove('d-none');

            renderSummaryCards(divisionCardsElement, byDivision, function (row) {
                return row.label || '-';
            }, 'category_id');
            renderSummaryCards(departmentCardsElement, byDepartment, function (row) {
                return row.label || '-';
            }, 'department_id');
        }

        function setLoadingState() {
            hideFeedback();
            clearSummary();
            loadingElement.classList.remove('d-none');
        }

        function setErrorState(message) {
            hideFeedback();
            clearSummary();
            errorElement.textContent = message;
            errorElement.classList.remove('d-none');
        }

        function setEmptyState() {
            hideFeedback();
            clearSummary();
            emptyElement.classList.remove('d-none');
        }

        async function loadDetailCards() {
            setLoadingState();

            try {
                const params = new URLSearchParams(window.location.search);
                params.delete('category_id');
                params.delete('department_id');
                params.set('stat', state.statKey);

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
                renderSummaries(payload.summaries || {});
            } catch (error) {
                setErrorState('Failed to load SOP details. Please try again.');
            }
        }

        function openModalForCard(card) {
            setContext(card);
            modal.show();
            loadDetailCards();
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

        modalElement.addEventListener('hidden.bs.modal', function () {
            cards.forEach(function (item) {
                item.classList.remove('kms-stat-selected');
            });
        });

        summaryWrapElement?.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-dashboard-filter-key][data-dashboard-filter-value]');
            if (!trigger) {
                return;
            }

            const filterKey = trigger.getAttribute('data-dashboard-filter-key');
            const filterValue = trigger.getAttribute('data-dashboard-filter-value');
            if (!filterKey || !filterValue) {
                return;
            }

            const params = new URLSearchParams(window.location.search);
            params.set(filterKey, filterValue);
            params.delete('page');
            window.location.assign(window.location.pathname + '?' + params.toString());
        });
    });
</script>
@endsection
