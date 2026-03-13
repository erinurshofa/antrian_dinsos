<?php

/** @var yii\web\View $this */
/** @var app\models\QueueType[] $queueTypes */
/** @var array $counts */

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'Ambil Antrian';

$csrfToken = Yii::$app->request->csrfToken;
?>

<div class="kiosk-screen" id="kiosk-screen">
    <div class="kiosk-header">
        <div class="kiosk-logo">🏛️</div>
        <h1>DINAS SOSIAL KOTA SEMARANG</h1>
        <p>Silakan pilih jenis antrian</p>
    </div>

    <div class="kiosk-buttons">
        <?php foreach ($queueTypes as $qt): ?>
        <div class="kiosk-btn <?= strtolower($qt->code) ?>" 
             onclick="takeQueue(<?= $qt->id ?>, '<?= $qt->name ?>')"
             id="btn-queue-<?= $qt->id ?>">
            <div class="kiosk-btn-icon">
                <?= $qt->code === 'UMUM' ? '👤' : '🤝' ?>
            </div>
            <div class="kiosk-btn-title"><?= Html::encode(strtoupper($qt->name)) ?></div>
            <div class="kiosk-btn-desc">
                <?= $qt->code === 'RENTAN' ? 'Lansia · Disabilitas · Ibu Hamil · Kondisi Khusus' : 'Layanan umum untuk semua pengunjung' ?>
            </div>
            <div class="kiosk-btn-count">
                <small>Antrian hari ini</small>
                <span id="count-<?= $qt->id ?>"><?= $counts[$qt->id] ?? 0 ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Ticket Modal -->
<div class="ticket-modal" id="ticketModal">
    <div class="ticket-card">
        <div class="ticket-office">DINAS SOSIAL KOTA SEMARANG</div>
        <div class="ticket-system">SISTEM ANTRIAN LAYANAN</div>
        <div class="ticket-number" id="ticket-number">---</div>
        <div class="ticket-type" id="ticket-type"></div>
        <div class="ticket-date" id="ticket-date"><?= date('d F Y') ?></div>
        <div class="ticket-msg">Silakan menunggu panggilan nomor antrian Anda</div>
        <button class="btn btn-primary btn-lg mt-4" onclick="closeTicket()" style="width:100%">
            <i class="bi bi-check-circle me-2"></i> OK
        </button>
    </div>
</div>

<!-- Print Area (Thermal Printer) -->
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
const TAKE_URL = '<?= Url::to(["queue/take"]) ?>';
const CSRF_TOKEN = '<?= $csrfToken ?>';
let isProcessing = false;

function takeQueue(queueTypeId, queueTypeName) {
    if (isProcessing) return;
    isProcessing = true;

    const btn = document.getElementById('btn-queue-' + queueTypeId);
    btn.style.opacity = '0.5';
    btn.style.pointerEvents = 'none';

    fetch(TAKE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': CSRF_TOKEN,
        },
        body: '_csrf=' + encodeURIComponent(CSRF_TOKEN) + '&queue_type_id=' + queueTypeId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            // Show ticket
            document.getElementById('ticket-number').textContent = d.queue_number;
            document.getElementById('ticket-type').textContent = d.queue_type;
            
            // Update print area
            document.getElementById('print-number').textContent = d.queue_number;
            document.getElementById('print-type').textContent = d.queue_type;
            document.getElementById('print-date').textContent = d.queue_date;

            // Update count
            const countEl = document.getElementById('count-' + queueTypeId);
            countEl.textContent = parseInt(countEl.textContent) + 1;

            // Show modal
            const modal = document.getElementById('ticketModal');
            modal.classList.add('show');

            // Auto print
            setTimeout(() => { window.print(); }, 300);
        } else {
            alert(data.message || 'Gagal mengambil antrian');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan. Silakan coba lagi.');
    })
    .finally(() => {
        isProcessing = false;
        btn.style.opacity = '1';
        btn.style.pointerEvents = 'auto';
    });
}

function closeTicket() {
    document.getElementById('ticketModal').classList.remove('show');
}

// Auto-close ticket after 10 seconds
setInterval(() => {
    const modal = document.getElementById('ticketModal');
    if (modal.classList.contains('show')) {
        // Will auto-close via user or remain for print
    }
}, 1000);

// Auto-close after 8 seconds
document.getElementById('ticketModal').addEventListener('transitionend', () => {
    setTimeout(closeTicket, 8000);
});
</script>
