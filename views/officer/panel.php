<?php

/** @var yii\web\View $this */
/** @var app\models\User $user */
/** @var app\models\Loket $loket */
/** @var app\models\Queue|null $currentQueue */
/** @var app\models\Service|null $currentService */
/** @var int $waitingCount */
/** @var int $completedCount */
/** @var array $serviceTypes */

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'Panel Petugas';

$csrfToken = Yii::$app->request->csrfToken;
?>

<div class="row g-4 mb-4">
    <!-- Stats Cards -->
    <div class="col-md-3">
        <div class="stat-card primary fade-in-up">
            <div class="stat-icon"><i class="bi bi-person-workspace"></i></div>
            <div class="stat-value"><?= Html::encode($loket->name) ?></div>
            <div class="stat-label">Loket Anda</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning fade-in-up delay-1">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value" id="stat-waiting"><?= $waitingCount ?></div>
            <div class="stat-label">Menunggu</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success fade-in-up delay-2">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value" id="stat-completed"><?= $completedCount ?></div>
            <div class="stat-label">Selesai Hari Ini</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card info fade-in-up delay-3">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-value" id="stat-time">--:--</div>
            <div class="stat-label">Waktu Layanan</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Current Queue Panel -->
    <div class="col-lg-5">
        <div class="card fade-in-up delay-1" id="queue-panel">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-broadcast me-2"></i> Antrian Saat Ini</span>
                <span class="badge bg-primary" id="queue-status-badge">
                    <?= $currentQueue ? ($currentQueue->status === 'serving' ? 'Dilayani' : 'Dipanggil') : 'Idle' ?>
                </span>
            </div>
            <div class="card-body">
                <div id="queue-active" style="<?= $currentQueue ? '' : 'display:none' ?>">
                    <div class="officer-current-queue">
                        <div class="officer-queue-number" id="current-queue-number">
                            <?= $currentQueue ? $currentQueue->queue_number : '---' ?>
                        </div>
                        <div class="officer-queue-type" id="current-queue-type">
                            <?= $currentQueue && $currentQueue->queueType ? $currentQueue->queueType->name : '' ?>
                        </div>
                    </div>
                    <div class="officer-actions">
                        <button class="btn btn-success" onclick="recallQueue()" id="btn-recall">
                            <i class="bi bi-megaphone me-2"></i> PANGGIL ULANG
                        </button>
                        <button class="btn btn-danger" onclick="completeQueue()" id="btn-complete">
                            <i class="bi bi-check-circle me-2"></i> SELESAI
                        </button>
                    </div>
                </div>

                <div id="queue-idle" style="<?= $currentQueue ? 'display:none' : '' ?>">
                    <div class="officer-idle">
                        <div class="idle-icon">📋</div>
                        <h3>Tidak ada antrian aktif</h3>
                        <p>Klik tombol PANGGIL untuk memanggil antrian berikutnya</p>
                        <button class="btn btn-primary btn-lg mt-3" onclick="callNextQueue()" id="btn-call">
                            <i class="bi bi-megaphone-fill me-2"></i> PANGGIL ANTRIAN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Data Entry -->
    <div class="col-lg-7">
        <div class="card fade-in-up delay-2" id="service-panel">
            <div class="card-header">
                <i class="bi bi-clipboard-data me-2"></i> Data Pelayanan
            </div>
            <div class="card-body">
                <div id="service-form-container" style="<?= $currentQueue ? '' : 'display:none' ?>">
                    <form id="serviceForm">
                        <input type="hidden" id="service_id" value="<?= $currentService ? $currentService->id : '' ?>">
                        <input type="hidden" id="queue_id" value="<?= $currentQueue ? $currentQueue->id : '' ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="svc-nama" required
                                       value="<?= $currentService ? Html::encode($currentService->nama) : '' ?>"
                                       placeholder="Nama pengunjung">
                                <div class="invalid-feedback">Nama wajib diisi</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select class="form-select" id="svc-jk" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="L" <?= ($currentService && $currentService->jenis_kelamin === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="P" <?= ($currentService && $currentService->jenis_kelamin === 'P') ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                                <div class="invalid-feedback">Jenis kelamin wajib dipilih</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. HP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="svc-hp" required
                                       value="<?= $currentService ? Html::encode($currentService->no_hp) : '' ?>"
                                       placeholder="08xxxxxxxxxx">
                                <div class="invalid-feedback">No. HP wajib diisi</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIK <small class="text-muted">(opsional)</small></label>
                                <input type="text" class="form-control" id="svc-nik" 
                                       value="<?= $currentService ? Html::encode($currentService->nik) : '' ?>"
                                       placeholder="16 digit NIK" maxlength="16">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Layanan <span class="text-danger">*</span></label>
                                <select class="form-select" id="svc-type" required>
                                    <option value="">-- Pilih --</option>
                                    <?php foreach ($serviceTypes as $id => $name): ?>
                                    <option value="<?= $id ?>" <?= ($currentService && $currentService->service_type_id == $id) ? 'selected' : '' ?>>
                                        <?= Html::encode($name) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Jenis layanan wajib dipilih</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Keperluan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="svc-keperluan" required
                                       value="<?= $currentService ? Html::encode($currentService->keperluan) : '' ?>"
                                       placeholder="Keperluan layanan">
                                <div class="invalid-feedback">Keperluan wajib diisi</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keterangan <small class="text-muted">(opsional)</small></label>
                                <textarea class="form-control" id="svc-keterangan" rows="2" 
                                          placeholder="Catatan tambahan"><?= $currentService ? Html::encode($currentService->keterangan) : '' ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-primary" onclick="saveService()" id="btn-save">
                                    <i class="bi bi-save me-2"></i> Simpan Data
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div id="service-empty" style="<?= $currentQueue ? 'display:none' : '' ?>">
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-clipboard" style="font-size:48px;opacity:0.2"></i>
                        <p class="mt-3">Panggil antrian terlebih dahulu untuk mengisi data pelayanan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const CALL_URL = '<?= Url::to(["officer/call"]) ?>';
const COMPLETE_URL = '<?= Url::to(["officer/complete"]) ?>';
const SAVE_URL = '<?= Url::to(["officer/save-service"]) ?>';
const CSRF = '<?= $csrfToken ?>';

let currentQueueId = <?= $currentQueue ? $currentQueue->id : 'null' ?>;
let currentServiceId = <?= $currentService && $currentService->id ? $currentService->id : 'null' ?>;
let serviceTimer = null;
let serviceStartTime = <?= $currentQueue && $currentQueue->served_at ? 'new Date("' . $currentQueue->served_at . '").getTime()' : 'null' ?>;

// ===== Call Next Queue =====
function callNextQueue() {
    const btn = document.getElementById('btn-call');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memanggil...';

    fetch(CALL_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_csrf=' + encodeURIComponent(CSRF)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            currentQueueId = d.queue_id;
            currentServiceId = d.service_id;
            serviceStartTime = Date.now();

            // Update UI
            document.getElementById('current-queue-number').textContent = d.queue_number;
            document.getElementById('current-queue-type').textContent = d.queue_type;
            document.getElementById('queue-status-badge').textContent = 'Dilayani';
            document.getElementById('queue-status-badge').className = 'badge bg-success';
            document.getElementById('queue_id').value = d.queue_id;
            document.getElementById('service_id').value = d.service_id;

            // Show active, hide idle
            document.getElementById('queue-active').style.display = '';
            document.getElementById('queue-idle').style.display = 'none';
            document.getElementById('service-form-container').style.display = '';
            document.getElementById('service-empty').style.display = 'none';

            // Clear form
            ['svc-nama', 'svc-hp', 'svc-nik', 'svc-keperluan', 'svc-keterangan'].forEach(id => {
                document.getElementById(id).value = '';
            });
            document.getElementById('svc-jk').value = '';
            document.getElementById('svc-type').value = '';

            // Update waiting count
            const waitEl = document.getElementById('stat-waiting');
            waitEl.textContent = Math.max(0, parseInt(waitEl.textContent) - 1);

            // Start timer
            startTimer();

            // Voice announcement
            announceQueue(d.queue_number, d.loket_name);
        } else {
            alert(data.message || 'Gagal memanggil antrian');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-megaphone-fill me-2"></i> PANGGIL ANTRIAN';
    });
}

// ===== Recall (re-announce) =====
function recallQueue() {
    const number = document.getElementById('current-queue-number').textContent.trim();
    const loketName = '<?= $loket->name ?>';
    announceQueue(number, loketName);
}

// ===== Complete Queue =====
function completeQueue() {
    if (!currentQueueId) return;
    if (!confirm('Selesaikan pelayanan antrian ini?')) return;

    fetch(COMPLETE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_csrf=' + encodeURIComponent(CSRF) + '&queue_id=' + currentQueueId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Reset UI
            document.getElementById('queue-active').style.display = 'none';
            document.getElementById('queue-idle').style.display = '';
            document.getElementById('service-form-container').style.display = 'none';
            document.getElementById('service-empty').style.display = '';
            document.getElementById('queue-status-badge').textContent = 'Idle';
            document.getElementById('queue-status-badge').className = 'badge bg-secondary';

            currentQueueId = null;
            currentServiceId = null;
            serviceStartTime = null;
            stopTimer();

            // Update completed count
            const compEl = document.getElementById('stat-completed');
            compEl.textContent = parseInt(compEl.textContent) + 1;
        } else {
            alert(data.message);
        }
    })
    .catch(err => alert('Terjadi kesalahan'));
}

// ===== Save Service Data =====
function saveService() {
    if (!currentServiceId) return;

    // Validate required fields
    const requiredFields = [
        { id: 'svc-nama', label: 'Nama' },
        { id: 'svc-jk', label: 'Jenis Kelamin' },
        { id: 'svc-hp', label: 'No. HP' },
        { id: 'svc-type', label: 'Jenis Layanan' },
        { id: 'svc-keperluan', label: 'Keperluan' },
    ];

    let isValid = true;
    let firstInvalid = null;

    // Reset all validation states
    document.querySelectorAll('#serviceForm .form-control, #serviceForm .form-select').forEach(el => {
        el.classList.remove('is-invalid');
    });

    // Check each required field
    requiredFields.forEach(f => {
        const el = document.getElementById(f.id);
        if (!el.value || el.value.trim() === '') {
            el.classList.add('is-invalid');
            isValid = false;
            if (!firstInvalid) firstInvalid = el;
        }
    });

    if (!isValid) {
        showToast('Harap isi semua field yang bertanda *', 'danger');
        if (firstInvalid) firstInvalid.focus();
        return;
    }

    const body = new URLSearchParams({
        _csrf: CSRF,
        service_id: currentServiceId,
        nama: document.getElementById('svc-nama').value.trim(),
        jenis_kelamin: document.getElementById('svc-jk').value,
        no_hp: document.getElementById('svc-hp').value.trim(),
        nik: document.getElementById('svc-nik').value.trim(),
        service_type_id: document.getElementById('svc-type').value,
        keperluan: document.getElementById('svc-keperluan').value.trim(),
        keterangan: document.getElementById('svc-keterangan').value.trim(),
    });

    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...';

    fetch(SAVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Data berhasil disimpan', 'success');
            // Clear invalid states
            document.querySelectorAll('#serviceForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        } else {
            showToast(data.message || 'Gagal menyimpan', 'danger');
        }
    })
    .catch(() => showToast('Terjadi kesalahan', 'danger'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-2"></i> Simpan Data';
    });
}

// Clear validation on input
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('is-invalid')) {
        e.target.classList.remove('is-invalid');
    }
});
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('is-invalid')) {
        e.target.classList.remove('is-invalid');
    }
});

// ===== Timer =====
function startTimer() {
    stopTimer();
    serviceTimer = setInterval(() => {
        if (!serviceStartTime) return;
        const elapsed = Math.floor((Date.now() - serviceStartTime) / 1000);
        const min = String(Math.floor(elapsed / 60)).padStart(2, '0');
        const sec = String(elapsed % 60).padStart(2, '0');
        document.getElementById('stat-time').textContent = min + ':' + sec;
    }, 1000);
}

function stopTimer() {
    if (serviceTimer) clearInterval(serviceTimer);
    document.getElementById('stat-time').textContent = '--:--';
}

if (serviceStartTime) startTimer();

// ===== Voice =====
function announceQueue(queueNumber, loketName) {
    if (!('speechSynthesis' in window)) return;

    const prefix = queueNumber.charAt(0);
    const number = parseInt(queueNumber.substring(1));
    const typeName = prefix === 'U' ? 'umum' : 'rentan';
    const digits = ['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan'];
    const numWords = String(number).split('').map(d => digits[parseInt(d)]).join(' ');

    const text = `Nomor antrian ${typeName} ${numWords}, silakan menuju ${loketName}`;

    window.speechSynthesis.cancel();
    const utter = new SpeechSynthesisUtterance(text);
    utter.lang = 'id-ID';
    utter.rate = 0.85;

    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(v => v.lang.startsWith('id'));
    if (idVoice) utter.voice = idVoice;

    window.speechSynthesis.speak(utter);

    // Repeat
    utter.onend = () => {
        setTimeout(() => {
            const r = new SpeechSynthesisUtterance(text);
            r.lang = 'id-ID'; r.rate = 0.85;
            if (idVoice) r.voice = idVoice;
            window.speechSynthesis.speak(r);
        }, 1200);
    };
}

// Load voices
if ('speechSynthesis' in window) {
    window.speechSynthesis.getVoices();
    window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
}

// ===== Toast =====
function showToast(msg, type) {
    const wrapper = document.createElement('div');
    wrapper.className = `alert alert-${type} position-fixed`;
    wrapper.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:250px;animation:fadeInUp 0.3s';
    wrapper.innerHTML = msg;
    document.body.appendChild(wrapper);
    setTimeout(() => wrapper.remove(), 3000);
}
</script>
