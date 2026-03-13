<?php

/** @var yii\web\View $this */
/** @var app\models\QueueType[] $queueTypes */
/** @var array $counts */
/** @var array $waitingCounts */
/** @var app\models\Queue[] $recentQueues */

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'Panel Satpam';

$csrfToken = Yii::$app->request->csrfToken;
?>

<div class="row g-4 mb-4">
    <?php foreach ($queueTypes as $qt): ?>
    <div class="col-md-6">
        <div class="card fade-in-up">
            <div class="card-body text-center py-5">
                <div style="font-size:48px;margin-bottom:16px">
                    <?= $qt->code === 'UMUM' ? '👤' : '🤝' ?>
                </div>
                <h4 class="fw-bold mb-2"><?= Html::encode($qt->name) ?></h4>
                <p class="text-muted mb-3">
                    <?= $qt->code === 'RENTAN' ? 'Lansia · Disabilitas · Ibu Hamil · Kondisi Khusus' : 'Layanan umum semua pengunjung' ?>
                </p>

                <div class="d-flex justify-content-center gap-4 mb-4">
                    <div>
                        <div class="fw-bold fs-3" style="font-family:'JetBrains Mono',monospace" id="total-<?= $qt->id ?>"><?= $counts[$qt->id] ?? 0 ?></div>
                        <small class="text-muted">Total</small>
                    </div>
                    <div>
                        <div class="fw-bold fs-3 text-warning" style="font-family:'JetBrains Mono',monospace" id="waiting-<?= $qt->id ?>"><?= $waitingCounts[$qt->id] ?? 0 ?></div>
                        <small class="text-muted">Menunggu</small>
                    </div>
                </div>

                <button class="btn btn-xl <?= $qt->code === 'UMUM' ? 'btn-primary' : 'btn-danger' ?>" 
                        onclick="takeQueue(<?= $qt->id ?>, '<?= $qt->name ?>')"
                        id="btn-take-<?= $qt->id ?>"
                        style="width:100%;max-width:320px;font-size:18px;padding:18px 32px">
                    <i class="bi bi-plus-circle me-2"></i>
                    AMBIL <?= strtoupper($qt->name) ?>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Last Ticket -->
<div class="card fade-in-up delay-2 mb-4" id="last-ticket-card" style="display:none">
    <div class="card-body text-center py-4">
        <div class="text-muted mb-2">Tiket Terakhir Diambil</div>
        <div id="last-ticket-number" style="font-size:48px;font-weight:900;font-family:'JetBrains Mono',monospace;color:var(--primary)">---</div>
        <div id="last-ticket-type" class="text-muted"></div>
        <button class="btn btn-outline-primary mt-3" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> Cetak Ulang
        </button>
    </div>
</div>

<!-- Recent Queues -->
<div class="card fade-in-up delay-3">
    <div class="card-header">
        <i class="bi bi-clock-history me-2"></i> Antrian Terakhir Hari Ini
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>No. Antrian</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody id="recent-table">
                    <?php foreach ($recentQueues as $q): ?>
                    <tr>
                        <td><strong style="font-family:'JetBrains Mono',monospace"><?= $q->queue_number ?></strong></td>
                        <td><?= $q->queueType ? $q->queueType->name : '-' ?></td>
                        <td><?= $q->statusLabel ?></td>
                        <td><?= date('H:i:s', $q->created_at) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentQueues)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada antrian hari ini</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Print Area -->
<div class="print-area" id="printArea">
    <div class="print-header">
        DINAS SOSIAL KOTA SEMARANG<br>
        SISTEM ANTRIAN LAYANAN
    </div>
    <div class="print-number" id="print-number">---</div>
    <div class="print-info">
        <span id="print-type"></span><br>
        Tanggal: <span id="print-date"><?= date('Y-m-d') ?></span><br><br>
        Silakan menunggu panggilan
    </div>
</div>

<script>
const TAKE_URL = '<?= Url::to(["security/take"]) ?>';
const CSRF = '<?= $csrfToken ?>';
let isProcessing = false;

function takeQueue(queueTypeId, queueTypeName) {
    if (isProcessing) return;
    isProcessing = true;

    const btn = document.getElementById('btn-take-' + queueTypeId);
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';

    fetch(TAKE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_csrf=' + encodeURIComponent(CSRF) + '&queue_type_id=' + queueTypeId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const d = data.data;

            // Update stats
            const totalEl = document.getElementById('total-' + queueTypeId);
            const waitEl = document.getElementById('waiting-' + queueTypeId);
            totalEl.textContent = parseInt(totalEl.textContent) + 1;
            waitEl.textContent = parseInt(waitEl.textContent) + 1;

            // Show last ticket
            document.getElementById('last-ticket-card').style.display = '';
            document.getElementById('last-ticket-number').textContent = d.queue_number;
            document.getElementById('last-ticket-type').textContent = d.queue_type;

            // Print
            document.getElementById('print-number').textContent = d.queue_number;
            document.getElementById('print-type').textContent = d.queue_type;
            document.getElementById('print-date').textContent = d.queue_date;

            setTimeout(() => window.print(), 300);

            // Add to recent table
            const tbody = document.getElementById('recent-table');
            const firstRow = tbody.querySelector('tr');
            if (firstRow && firstRow.querySelector('td[colspan]')) {
                tbody.innerHTML = '';
            }
            const tr = document.createElement('tr');
            tr.style.animation = 'fadeInUp 0.3s ease';
            tr.innerHTML = `
                <td><strong style="font-family:'JetBrains Mono',monospace">${d.queue_number}</strong></td>
                <td>${d.queue_type}</td>
                <td><span class="badge bg-warning text-dark">Menunggu</span></td>
                <td>${d.created_at}</td>
            `;
            tbody.insertBefore(tr, tbody.firstChild);
        } else {
            alert(data.message || 'Gagal mengambil antrian');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan');
    })
    .finally(() => {
        isProcessing = false;
        btn.disabled = false;
        const icon = queueTypeName.includes('Rentan') ? '' : '';
        btn.innerHTML = `<i class="bi bi-plus-circle me-2"></i> AMBIL ${queueTypeName.toUpperCase()}`;
    });
}
</script>
