<?php

/** @var yii\web\View $this */
/** @var app\models\Service[] $services */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var string $search */
/** @var string $queueType */
/** @var app\models\QueueType[] $queueTypes */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var int $totalPages */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Riwayat Layanan';
?>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card primary fade-in-up">
            <div class="stat-icon"><i class="bi bi-clipboard-check"></i></div>
            <div class="stat-value"><?= number_format($total) ?></div>
            <div class="stat-label">Total Data Ditemukan</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card info fade-in-up delay-1">
            <div class="stat-icon"><i class="bi bi-calendar-range"></i></div>
            <div class="stat-value" style="font-size:18px"><?= date('d/m/Y', strtotime($dateFrom)) ?> — <?= date('d/m/Y', strtotime($dateTo)) ?></div>
            <div class="stat-label">Rentang Tanggal</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success fade-in-up delay-2">
            <div class="stat-icon"><i class="bi bi-file-earmark-spreadsheet"></i></div>
            <div class="stat-value">
                <a href="<?= Url::to(['officer/export', 'date_from' => $dateFrom, 'date_to' => $dateTo, 'search' => $search, 'queue_type' => $queueType]) ?>" 
                   class="btn btn-success btn-lg" id="btn-export">
                    <i class="bi bi-download me-2"></i> Export CSV
                </a>
            </div>
            <div class="stat-label">Download Laporan</div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card fade-in-up delay-1 mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-funnel me-2"></i> Filter Data</span>
        <a href="<?= Url::to(['officer/history']) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
        </a>
    </div>
    <div class="card-body">
        <form method="get" action="<?= Url::to(['officer/history']) ?>" id="filter-form">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" name="date_from" value="<?= Html::encode($dateFrom) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="date_to" value="<?= Html::encode($dateTo) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipe Antrian</label>
                    <select class="form-select" name="queue_type">
                        <option value="">Semua</option>
                        <?php foreach ($queueTypes as $qt): ?>
                        <option value="<?= $qt->id ?>" <?= $queueType == $qt->id ? 'selected' : '' ?>><?= Html::encode($qt->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cari</label>
                    <input type="text" class="form-control" name="search" value="<?= Html::encode($search) ?>" 
                           placeholder="Nama, No. HP, NIK, No. Antrian...">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card fade-in-up delay-2">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-table me-2"></i> Data Layanan Selesai</span>
        <span class="badge bg-primary"><?= number_format($total) ?> data</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="history-table">
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th>Tanggal</th>
                        <th>No. Antrian</th>
                        <th>Tipe</th>
                        <th>Nama</th>
                        <th>JK</th>
                        <th>No. HP</th>
                        <th>Layanan</th>
                        <th>Keperluan</th>
                        <th>Loket</th>
                        <th>Petugas</th>
                        <th>Durasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="12" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size:48px;opacity:0.3"></i>
                            <p class="mt-3 mb-0">Tidak ada data ditemukan</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($services as $idx => $svc): ?>
                    <?php $q = $svc->queue; ?>
                    <tr>
                        <td class="text-muted"><?= ($page - 1) * $perPage + $idx + 1 ?></td>
                        <td class="text-nowrap"><?= $q ? date('d/m/Y', strtotime($q->queue_date)) : '-' ?></td>
                        <td>
                            <span class="fw-bold font-monospace"><?= $q ? Html::encode($q->queue_number) : '-' ?></span>
                        </td>
                        <td>
                            <?php if ($q && $q->queueType): ?>
                            <span class="badge" style="background:<?= Html::encode($q->queueType->color ?? '#6c757d') ?>">
                                <?= Html::encode($q->queueType->name) ?>
                            </span>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td><?= Html::encode($svc->nama ?: '-') ?></td>
                        <td><?= $svc->jenis_kelamin === 'L' ? '<span class="text-primary">L</span>' : ($svc->jenis_kelamin === 'P' ? '<span class="text-danger">P</span>' : '-') ?></td>
                        <td class="text-nowrap"><?= Html::encode($svc->no_hp ?: '-') ?></td>
                        <td><?= $svc->serviceType ? Html::encode($svc->serviceType->name) : '-' ?></td>
                        <td><?= Html::encode($svc->keperluan ?: '-') ?></td>
                        <td><?= $svc->loket ? Html::encode($svc->loket->name) : '-' ?></td>
                        <td><?= $svc->officer ? Html::encode($svc->officer->fullname) : '-' ?></td>
                        <td class="text-nowrap"><?= $svc->getDurationFormatted() ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Menampilkan <?= ($page - 1) * $perPage + 1 ?>–<?= min($page * $perPage, $total) ?> dari <?= number_format($total) ?> data
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= Url::to(['officer/history', 'page' => $page - 1, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'search' => $search, 'queue_type' => $queueType]) ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= Url::to(['officer/history', 'page' => $i, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'search' => $search, 'queue_type' => $queueType]) ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= Url::to(['officer/history', 'page' => $page + 1, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'search' => $search, 'queue_type' => $queueType]) ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
