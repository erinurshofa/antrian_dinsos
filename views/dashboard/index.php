<?php

/** @var yii\web\View $this */
/** @var int $totalToday */
/** @var int $completedToday */
/** @var int $waitingNow */
/** @var int $servingNow */
/** @var int $avgDuration */
/** @var array $serviceTypes */
/** @var app\models\QueueType[] $queueTypes */
/** @var app\models\Loket[] $lokets */

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'Dashboard';
?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card primary fade-in-up">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value"><?= $totalToday ?></div>
            <div class="stat-label">Total Antrian Hari Ini</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card success fade-in-up delay-1">
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-value"><?= $completedToday ?></div>
            <div class="stat-label">Selesai Dilayani</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card warning fade-in-up delay-2">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value"><?= $waitingNow ?></div>
            <div class="stat-label">Menunggu Saat Ini</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card info fade-in-up delay-3">
            <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
            <div class="stat-value"><?= $avgDuration > 0 ? round($avgDuration / 60, 1) : '0' ?><small style="font-size:14px;font-weight:400"> mnt</small></div>
            <div class="stat-label">Rata-rata Waktu Layanan</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filter-bar fade-in-up delay-2">
    <div class="form-group">
        <label class="form-label">Tanggal Mulai</label>
        <input type="date" class="form-control" id="filter-start" value="<?= date('Y-m-d') ?>">
    </div>
    <div class="form-group">
        <label class="form-label">Tanggal Akhir</label>
        <input type="date" class="form-control" id="filter-end" value="<?= date('Y-m-d') ?>">
    </div>
    <div class="form-group">
        <button class="btn btn-primary" onclick="loadCharts()">
            <i class="bi bi-funnel me-2"></i> Filter
        </button>
    </div>
    <div class="form-group">
        <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-2"></i> Export
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="#" onclick="exportData('excel')">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i> Export Excel
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" onclick="exportData('pdf')">
                        <i class="bi bi-file-earmark-pdf me-2"></i> Export PDF
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card fade-in-up">
            <div class="card-header">
                <i class="bi bi-graph-up me-2"></i> Tren Antrian Harian
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartDaily"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card fade-in-up delay-1">
            <div class="card-header">
                <i class="bi bi-pie-chart me-2"></i> Distribusi Layanan
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartService"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card fade-in-up">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2"></i> Jam Sibuk (Peak Hours)
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartPeakHours"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card fade-in-up delay-1">
            <div class="card-header">
                <i class="bi bi-gender-ambiguous me-2"></i> Gender
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartGender"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card fade-in-up delay-2">
            <div class="card-header">
                <i class="bi bi-speedometer2 me-2"></i> Kinerja Loket
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartLoket"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const STATS_URL = '<?= Url::to(["dashboard/stats"]) ?>';
const EXPORT_EXCEL_URL = '<?= Url::to(["dashboard/export-excel"]) ?>';
const EXPORT_PDF_URL = '<?= Url::to(["dashboard/export-pdf"]) ?>';

// Chart instances
let chartDaily, chartService, chartPeakHours, chartGender, chartLoket;

// Color palette
const COLORS = {
    primary: '#3b82f6',
    primaryLight: 'rgba(59,130,246,0.1)',
    danger: '#ef4444',
    dangerLight: 'rgba(239,68,68,0.1)',
    success: '#10b981',
    warning: '#f59e0b',
    purple: '#8b5cf6',
    pink: '#ec4899',
    cyan: '#06b6d4',
    orange: '#f97316',
};

const palette = [COLORS.primary, COLORS.danger, COLORS.success, COLORS.warning, COLORS.purple, COLORS.pink, COLORS.cyan, COLORS.orange];

// Chart default options
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#64748b';

function loadCharts() {
    const startDate = document.getElementById('filter-start').value;
    const endDate = document.getElementById('filter-end').value;

    fetch(`${STATS_URL}?start_date=${startDate}&end_date=${endDate}`)
        .then(r => r.json())
        .then(res => {
            if (res.success) renderCharts(res.data);
        })
        .catch(err => console.error(err));
}

function renderCharts(data) {
    // ===== Daily Trend (Line) =====
    const dailyDates = [...new Set(data.dailyCounts.map(d => d.queue_date))];
    const umumData = dailyDates.map(date => {
        const found = data.dailyCounts.find(d => d.queue_date === date && d.queue_type_id == 1);
        return found ? parseInt(found.total) : 0;
    });
    const rentanData = dailyDates.map(date => {
        const found = data.dailyCounts.find(d => d.queue_date === date && d.queue_type_id == 2);
        return found ? parseInt(found.total) : 0;
    });

    if (chartDaily) chartDaily.destroy();
    chartDaily = new Chart(document.getElementById('chartDaily'), {
        type: 'line',
        data: {
            labels: dailyDates,
            datasets: [
                {
                    label: 'Antrian Umum',
                    data: umumData,
                    borderColor: COLORS.primary,
                    backgroundColor: COLORS.primaryLight,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                },
                {
                    label: 'Antrian Rentan',
                    data: rentanData,
                    borderColor: COLORS.danger,
                    backgroundColor: COLORS.dangerLight,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // ===== Service Distribution (Doughnut) =====
    if (chartService) chartService.destroy();
    chartService = new Chart(document.getElementById('chartService'), {
        type: 'doughnut',
        data: {
            labels: data.serviceDistribution.map(d => d.name || 'Lainnya'),
            datasets: [{
                data: data.serviceDistribution.map(d => parseInt(d.total)),
                backgroundColor: palette,
                borderWidth: 0,
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle' } },
            }
        }
    });

    // ===== Peak Hours (Bar) =====
    const hours = data.peakHours.map(d => d.hour + ':00');
    const hourCounts = data.peakHours.map(d => parseInt(d.total));

    if (chartPeakHours) chartPeakHours.destroy();
    chartPeakHours = new Chart(document.getElementById('chartPeakHours'), {
        type: 'bar',
        data: {
            labels: hours,
            datasets: [{
                label: 'Jumlah Antrian',
                data: hourCounts,
                backgroundColor: hourCounts.map((v, i) => {
                    const max = Math.max(...hourCounts);
                    return v === max ? COLORS.danger : COLORS.primary;
                }),
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // ===== Gender (Doughnut) =====
    if (chartGender) chartGender.destroy();
    const genderLabels = data.genderDistribution.map(d => d.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan');
    const genderData = data.genderDistribution.map(d => parseInt(d.total));
    chartGender = new Chart(document.getElementById('chartGender'), {
        type: 'doughnut',
        data: {
            labels: genderLabels,
            datasets: [{
                data: genderData,
                backgroundColor: [COLORS.primary, COLORS.pink],
                borderWidth: 0,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle' } },
            }
        }
    });

    // ===== Loket Performance (Bar) =====
    if (chartLoket) chartLoket.destroy();
    chartLoket = new Chart(document.getElementById('chartLoket'), {
        type: 'bar',
        data: {
            labels: data.loketPerformance.map(d => d.name || 'N/A'),
            datasets: [{
                label: 'Total Layanan',
                data: data.loketPerformance.map(d => parseInt(d.total)),
                backgroundColor: palette,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
                y: { grid: { display: false } }
            }
        }
    });
}

function exportData(type) {
    const startDate = document.getElementById('filter-start').value;
    const endDate = document.getElementById('filter-end').value;
    const url = type === 'excel' ? EXPORT_EXCEL_URL : EXPORT_PDF_URL;
    window.open(`${url}?start_date=${startDate}&end_date=${endDate}`, '_blank');
}

// Load on page load
document.addEventListener('DOMContentLoaded', loadCharts);
</script>
