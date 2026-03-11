@extends('layouts.app')

@section('page_title', 'Analytics')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h2 class="fw-bold mb-1">Analytics Dashboard</h2>
            <p class="text-secondary mb-0">SOP usage insights and engagement metrics.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.analytics.export') }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Export SOPs</a>
            <a href="{{ route('admin.analytics.export') }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Export Analytics</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-3">
            <div class="kms-stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="kms-stat-title">Total SOPs</div>
                    <span class="kms-analytics-icon text-info"><i class="bi bi-file-earmark-text"></i></span>
                </div>
                <div class="kms-stat-value">{{ number_format($totals['all']) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="kms-stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="kms-stat-title">Total Views</div>
                    <span class="kms-analytics-icon text-primary"><i class="bi bi-eye"></i></span>
                </div>
                <div class="kms-stat-value">{{ number_format($totals['views']) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="kms-stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="kms-stat-title">Total Likes</div>
                    <span class="kms-analytics-icon text-danger"><i class="bi bi-heart"></i></span>
                </div>
                <div class="kms-stat-value">{{ number_format($totals['likes']) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="kms-stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="kms-stat-title">Total Comments</div>
                    <span class="kms-analytics-icon text-success"><i class="bi bi-chat"></i></span>
                </div>
                <div class="kms-stat-value">{{ number_format($totals['comments']) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="kms-table-wrap p-4 h-100">
                <h4 class="fw-semibold mb-3"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Views & Comments Over Time</h4>
                <div class="kms-chart-wrap">
                    <canvas id="viewsTrendChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="kms-table-wrap p-4 h-100">
                <h4 class="fw-semibold mb-3"><i class="bi bi-eye text-primary me-2"></i>Top 5 Most Viewed</h4>
                <div class="kms-chart-wrap">
                    <canvas id="topViewedChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="kms-table-wrap p-4 h-100">
                <h4 class="fw-semibold mb-3"><i class="bi bi-pie-chart text-success me-2"></i>Views by Department</h4>
                <div class="kms-chart-wrap">
                    <canvas id="departmentViewsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="kms-table-wrap p-4 h-100">
                <h4 class="fw-semibold mb-3"><i class="bi bi-heart text-danger me-2"></i>Top 5 Most Liked</h4>
                <div class="kms-chart-wrap">
                    <canvas id="topLikedChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const css = getComputedStyle(document.documentElement);
        const textColor = css.getPropertyValue('--kms-text').trim() || '#152033';
        const mutedColor = css.getPropertyValue('--kms-muted').trim() || '#5f6f86';
        const borderColor = css.getPropertyValue('--kms-border').trim() || '#dbe2ee';
        const primaryColor = css.getPropertyValue('--kms-primary').trim() || '#f26a21';

        const viewsTrendLabels = @json($viewsTrendLabels);
        const viewsTrendData = @json($viewsTrendData);
        const commentsTrendData = @json($commentsTrendData);

        const topViewedLabels = @json($topViewedLabels);
        const topViewedData = @json($topViewedData);

        const topLikedLabels = @json($topLikedLabels);
        const topLikedData = @json($topLikedData);

        const departmentLabels = @json($departmentLabels);
        const departmentData = @json($departmentData);

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: textColor } }
            },
            scales: {
                x: {
                    ticks: { color: mutedColor },
                    grid: { color: borderColor }
                },
                y: {
                    ticks: { color: mutedColor },
                    grid: { color: borderColor }
                }
            }
        };

        const viewsTrendCanvas = document.getElementById('viewsTrendChart');
        if (viewsTrendCanvas) {
            new Chart(viewsTrendCanvas, {
                type: 'line',
                data: {
                    labels: viewsTrendLabels,
                    datasets: [
                        {
                            label: 'Views',
                            data: viewsTrendData,
                            borderColor: primaryColor,
                            backgroundColor: 'rgba(242, 106, 33, 0.14)',
                            fill: true,
                            borderWidth: 2.5,
                            pointRadius: 3,
                            tension: 0.35
                        },
                        {
                            label: 'Comments',
                            data: commentsTrendData,
                            borderColor: '#22a38e',
                            backgroundColor: 'rgba(34, 163, 142, 0.12)',
                            fill: true,
                            borderWidth: 2.2,
                            pointRadius: 3,
                            tension: 0.35
                        }
                    ]
                },
                options: commonOptions
            });
        }

        const topViewedCanvas = document.getElementById('topViewedChart');
        if (topViewedCanvas) {
            new Chart(topViewedCanvas, {
                type: 'bar',
                data: {
                    labels: topViewedLabels.length ? topViewedLabels : ['No data'],
                    datasets: [{
                        data: topViewedData.length ? topViewedData.map(v => Number(v) || 0) : [0],
                        backgroundColor: '#ea6a12',
                        borderRadius: 8
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { color: mutedColor, precision: 0 },
                            grid: { color: borderColor }
                        },
                        y: {
                            ticks: { color: mutedColor },
                            grid: { color: borderColor }
                        }
                    }
                }
            });
        }

        const deptCanvas = document.getElementById('departmentViewsChart');
        if (deptCanvas) {
            new Chart(deptCanvas, {
                type: 'doughnut',
                data: {
                    labels: departmentLabels,
                    datasets: [{
                        data: departmentData,
                        backgroundColor: ['#f26a21', '#0ea5e9', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#14b8a6'],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: textColor, boxWidth: 12 }
                        }
                    }
                }
            });
        }

        const topLikedCanvas = document.getElementById('topLikedChart');
        if (topLikedCanvas) {
            new Chart(topLikedCanvas, {
                type: 'bar',
                data: {
                    labels: topLikedLabels.length ? topLikedLabels : ['No data'],
                    datasets: [{
                        data: topLikedData.length ? topLikedData.map(v => Number(v) || 0) : [0],
                        backgroundColor: '#22a38e',
                        borderRadius: 8
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { color: mutedColor, precision: 0 },
                            grid: { color: borderColor }
                        },
                        y: {
                            ticks: { color: mutedColor },
                            grid: { color: borderColor }
                        }
                    }
                }
            });
        }
    })();
</script>
@endsection
