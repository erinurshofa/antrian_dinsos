<?php

/** @var yii\web\View $this */
/** @var app\models\Queue[] $serving */
/** @var array $waitingCounts */
/** @var app\models\QueueType[] $queueTypes */

use yii\helpers\Url;

$this->title = 'Display Antrian';
?>

<div class="display-screen" id="display-screen" style="position:relative;min-height:100vh;display:flex;flex-direction:column;overflow:auto;padding-bottom:20px">
    
    <!-- Header with clock -->
    <div class="display-header" style="position:relative;padding:20px 40px;flex-shrink:0">
        <h1 style="font-size:32px">DINAS SOSIAL KOTA SEMARANG</h1>
        <p style="font-size:18px">Sistem Antrian Layanan</p>
        <div style="position:absolute;top:20px;right:40px;text-align:right">
            <div id="display-clock" style="font-size:52px;font-weight:900;font-family:'JetBrains Mono',monospace;color:#fff;line-height:1"></div>
            <div id="display-date" style="font-size:18px;color:rgba(255,255,255,0.6);margin-top:4px"></div>
        </div>
    </div>

    <!-- Two Active Queue Panels: UMUM & RENTAN side-by-side -->
    <div style="display:flex;gap:20px;padding:0 40px;flex-shrink:0" id="serving-panels">
        <!-- Panel UMUM -->
        <div id="panel-umum" style="flex:1;background:linear-gradient(135deg,rgba(59,130,246,0.12),rgba(59,130,246,0.04));border:1px solid rgba(59,130,246,0.2);border-radius:20px;padding:30px;text-align:center;min-height:200px;display:flex;flex-direction:column;justify-content:center;transition:all 0.3s">
            <div style="font-size:18px;text-transform:uppercase;letter-spacing:3px;color:rgba(59,130,246,0.7);font-weight:700;margin-bottom:8px">👤 Antrian Umum</div>
            <div id="umum-number" style="font-size:96px;font-weight:900;font-family:'JetBrains Mono',monospace;color:#3b82f6;line-height:1">---</div>
            <div id="umum-loket" style="font-size:22px;color:rgba(255,255,255,0.5);margin-top:10px">Menunggu panggilan</div>
        </div>
        <!-- Panel RENTAN -->
        <div id="panel-rentan" style="flex:1;background:linear-gradient(135deg,rgba(239,68,68,0.12),rgba(239,68,68,0.04));border:1px solid rgba(239,68,68,0.2);border-radius:20px;padding:30px;text-align:center;min-height:200px;display:flex;flex-direction:column;justify-content:center;transition:all 0.3s">
            <div style="font-size:18px;text-transform:uppercase;letter-spacing:3px;color:rgba(239,68,68,0.7);font-weight:700;margin-bottom:8px">🤝 Antrian Rentan</div>
            <div id="rentan-number" style="font-size:96px;font-weight:900;font-family:'JetBrains Mono',monospace;color:#ef4444;line-height:1">---</div>
            <div id="rentan-loket" style="font-size:22px;color:rgba(255,255,255,0.5);margin-top:10px">Menunggu panggilan</div>
        </div>
    </div>

    <!-- Waiting counts bar -->
    <div class="display-waiting-bar" id="waiting-bar" style="position:static;margin:16px 40px;border-radius:12px;font-size:20px">
        <?php foreach ($queueTypes as $qt): ?>
        <div class="display-waiting-item">
            <span style="font-size:20px"><?= $qt->name ?> menunggu:</span>
            <span class="waiting-count" id="waiting-<?= $qt->id ?>" style="font-size:24px;font-weight:800">0</span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Called Queue History Table -->
    <div class="display-history" id="display-history" style="padding:0 40px;flex:1">
        <div style="background:rgba(255,255,255,0.05);border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.08)">
            <div style="background:rgba(255,255,255,0.06);padding:14px 24px;display:flex;align-items:center;gap:10px">
                <i class="bi bi-list-ol" style="color:var(--primary);font-size:22px"></i>
                <span style="font-weight:600;font-size:20px;color:rgba(255,255,255,0.9)">Riwayat Antrian Dipanggil</span>
            </div>
            <div style="max-height:360px;overflow-y:auto" id="history-scroll">
                <table style="width:100%;border-collapse:collapse" id="history-table">
                    <thead>
                        <tr style="background:rgba(255,255,255,0.03)">
                            <th style="padding:14px 28px;text-align:left;color:rgba(255,255,255,0.5);font-size:16px;font-weight:600;text-transform:uppercase;letter-spacing:1px">No. Antrian</th>
                            <th style="padding:14px 28px;text-align:left;color:rgba(255,255,255,0.5);font-size:16px;font-weight:600;text-transform:uppercase;letter-spacing:1px">Loket</th>
                            <th style="padding:14px 28px;text-align:center;color:rgba(255,255,255,0.5);font-size:16px;font-weight:600;text-transform:uppercase;letter-spacing:1px">Status</th>
                        </tr>
                    </thead>
                    <tbody id="history-body">
                        <tr><td colspan="3" style="padding:30px;text-align:center;color:rgba(255,255,255,0.3);font-size:20px">Belum ada antrian dipanggil</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const STREAM_URL = '<?= Url::to(["api/queue/stream", "office_id" => 1]) ?>';
const STATUS_URL = '<?= Url::to(["api/queue/status", "office_id" => 1]) ?>';

let lastServingHash = '';

// ===== Clock =====
function updateClock() {
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    const ss = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('display-clock').textContent = `${hh}:${mm}:${ss}`;

    const dateStr = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('display-date').textContent = dateStr;
}
setInterval(updateClock, 1000);
updateClock();

// ===== Display Renderer =====
function renderDisplay(data) {
    const serving = data.serving || [];
    const waiting = data.waiting || {};
    const history = data.history || [];

    // Check if data changed
    const newHash = JSON.stringify(serving);
    const changed = (newHash !== lastServingHash);
    lastServingHash = newHash;

    // ====== Update UMUM & RENTAN panels ======
    const umumQueue = serving.find(s => s.queue_type_code === 'UMUM');
    const rentanQueue = serving.find(s => s.queue_type_code !== 'UMUM');

    // Umum panel
    const umumNum = document.getElementById('umum-number');
    const umumLoket = document.getElementById('umum-loket');
    const panelUmum = document.getElementById('panel-umum');
    if (umumQueue) {
        umumNum.textContent = umumQueue.queue_number;
        umumLoket.innerHTML = '<i class="bi bi-geo-alt me-1"></i>' + umumQueue.loket_name;
        umumLoket.style.color = 'rgba(59,130,246,0.9)';
        panelUmum.style.borderColor = 'rgba(59,130,246,0.5)';
        panelUmum.style.boxShadow = '0 0 30px rgba(59,130,246,0.15)';
        if (changed) umumNum.style.animation = 'pulse-num 0.6s ease';
    } else {
        // Show last completed umum from history
        const lastUmum = history.find(h => h.queue_type_code === 'UMUM');
        if (lastUmum) {
            umumNum.textContent = lastUmum.queue_number;
            umumLoket.innerHTML = '<i class="bi bi-check-circle me-1"></i>Selesai — ' + lastUmum.loket_name;
            umumLoket.style.color = 'rgba(59,130,246,0.4)';
        } else {
            umumNum.textContent = '---';
            umumLoket.textContent = 'Menunggu panggilan';
            umumLoket.style.color = 'rgba(255,255,255,0.5)';
        }
        panelUmum.style.borderColor = 'rgba(59,130,246,0.15)';
        panelUmum.style.boxShadow = 'none';
    }

    // Rentan panel
    const rentanNum = document.getElementById('rentan-number');
    const rentanLoket = document.getElementById('rentan-loket');
    const panelRentan = document.getElementById('panel-rentan');
    if (rentanQueue) {
        rentanNum.textContent = rentanQueue.queue_number;
        rentanLoket.innerHTML = '<i class="bi bi-geo-alt me-1"></i>' + rentanQueue.loket_name;
        rentanLoket.style.color = 'rgba(239,68,68,0.9)';
        panelRentan.style.borderColor = 'rgba(239,68,68,0.5)';
        panelRentan.style.boxShadow = '0 0 30px rgba(239,68,68,0.15)';
        if (changed) rentanNum.style.animation = 'pulse-num 0.6s ease';
    } else {
        // Show last completed rentan from history
        const lastRentan = history.find(h => h.queue_type_code !== 'UMUM');
        if (lastRentan) {
            rentanNum.textContent = lastRentan.queue_number;
            rentanLoket.innerHTML = '<i class="bi bi-check-circle me-1"></i>Selesai — ' + lastRentan.loket_name;
            rentanLoket.style.color = 'rgba(239,68,68,0.4)';
        } else {
            rentanNum.textContent = '---';
            rentanLoket.textContent = 'Menunggu panggilan';
            rentanLoket.style.color = 'rgba(255,255,255,0.5)';
        }
        panelRentan.style.borderColor = 'rgba(239,68,68,0.15)';
        panelRentan.style.boxShadow = 'none';
    }

    // Reset animation after it plays
    if (changed) {
        setTimeout(() => {
            umumNum.style.animation = '';
            rentanNum.style.animation = '';
        }, 700);
    }

    // ====== Update waiting counts ======
    for (const [typeId, count] of Object.entries(waiting)) {
        const el = document.getElementById('waiting-' + typeId);
        if (el) el.textContent = count;
    }

    // ====== Render called history table ======
    const tbody = document.getElementById('history-body');
    if (history.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="padding:30px;text-align:center;color:rgba(255,255,255,0.3);font-size:20px">Belum ada antrian dipanggil</td></tr>';
    } else {
        let rows = '';
        history.forEach((h, idx) => {
            const isServing = (h.status === 'called' || h.status === 'serving');
            const statusBadge = h.status === 'completed' 
                ? '<span style="background:rgba(16,185,129,0.15);color:#10b981;padding:6px 18px;border-radius:20px;font-size:16px;font-weight:700">✅ Selesai</span>'
                : h.status === 'serving'
                ? '<span style="background:rgba(59,130,246,0.15);color:#3b82f6;padding:6px 18px;border-radius:20px;font-size:16px;font-weight:700">🔵 Dilayani</span>'
                : '<span style="background:rgba(245,158,11,0.15);color:#f59e0b;padding:6px 18px;border-radius:20px;font-size:16px;font-weight:700">📢 Dipanggil</span>';
            
            const rowBg = isServing ? 'background:rgba(59,130,246,0.06)' : (idx % 2 === 0 ? '' : 'background:rgba(255,255,255,0.02)');
            
            rows += `
                <tr style="${rowBg};border-bottom:1px solid rgba(255,255,255,0.04);${isServing ? 'animation:pulse-row 2s infinite' : ''}">
                    <td style="padding:14px 28px;font-family:'JetBrains Mono',monospace;font-weight:800;font-size:26px;color:${isServing ? '#3b82f6' : 'rgba(255,255,255,0.85)'}">${h.queue_number}</td>
                    <td style="padding:14px 28px;color:rgba(255,255,255,0.7);font-size:20px;font-weight:600">${h.loket_name}</td>
                    <td style="padding:14px 28px;text-align:center">${statusBadge}</td>
                </tr>
            `;
        });
        tbody.innerHTML = rows;
    }

    // Play sound + voice if changed and there are active queues
    if (changed && serving.length > 0) {
        playNotification();
        const latest = serving[0];
        announceQueue(latest.queue_number, latest.loket_name);
    }
}

// ===== Voice Announcement =====
// Preferred female voice (cached)
let cachedFemaleVoice = null;

function getPreferredVoice() {
    if (cachedFemaleVoice) return cachedFemaleVoice;
    const voices = window.speechSynthesis.getVoices();
    if (!voices.length) return null;

    // Debug: log all available voices
    console.log('🔊 Available voices:');
    voices.forEach((v, i) => console.log(`  [${i}] ${v.name} (${v.lang}) ${v.localService ? 'local' : 'remote'}`));

    // Priority order for female Indonesian voice
    const priorities = [
        // 1. Microsoft Gadis — the Windows female Indonesian voice
        v => v.lang.startsWith('id') && /gadis/i.test(v.name),
        // 2. Indonesian female voices by keyword
        v => v.lang.startsWith('id') && /female|perempuan|wanita|woman/i.test(v.name),
        // 3. Any Indonesian voice with feminine name indicators
        v => v.lang.startsWith('id') && /damayanti|siti|kartini|rani|dewi|putri|sri|ani|ina|ida/i.test(v.name),
        // 4. Google Indonesian (usually female by default)
        v => v.lang.startsWith('id') && /google/i.test(v.name),
        // 5. Any Indonesian voice
        v => v.lang.startsWith('id'),
        // 6. Malay female (close to Indonesian)
        v => v.lang.startsWith('ms') && /female|perempuan|wanita|gadis/i.test(v.name),
        // 7. Any Malay voice
        v => v.lang.startsWith('ms'),
    ];

    for (const pred of priorities) {
        const found = voices.find(pred);
        if (found) {
            cachedFemaleVoice = found;
            console.log('✅ Selected voice:', found.name, found.lang);
            return found;
        }
    }
    return null;
}

function announceQueue(queueNumber, loketName) {
    if (!('speechSynthesis' in window)) return;

    const prefix = queueNumber.charAt(0);
    const number = parseInt(queueNumber.substring(1));
    const typeName = prefix === 'U' ? 'umum' : 'rentan';
    const numberText = numberToIndonesian(number);

    // Extract loket number for natural reading
    const loketMatch = loketName.match(/\d+/);
    const loketSpoken = loketMatch ? `loket ${numberToIndonesian(parseInt(loketMatch[0]))}` : loketName;
    
    const text = `Nomor antrian ${typeName} ${numberText} silakan menuju ke ${loketSpoken}`;

    window.speechSynthesis.cancel();

    const voice = getPreferredVoice();

    // Repeat 3 times with short pause between each
    let repeatCount = 0;
    const totalRepeats = 3;

    function doSpeak() {
        speakText(text, voice, () => {
            repeatCount++;
            if (repeatCount < totalRepeats) {
                setTimeout(doSpeak, 800);
            }
        });
    }

    setTimeout(doSpeak, 300);
}

function speakText(text, voice, onEnd) {
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID';
    utterance.rate = 1.05;   // Slightly faster — energetic & semangat
    utterance.pitch = 1.4;   // Higher pitch — feminine, enthusiastic
    utterance.volume = 1.0;
    if (voice) utterance.voice = voice;
    if (onEnd) utterance.onend = onEnd;
    window.speechSynthesis.speak(utterance);
}

// ===== Indonesian Number to Words =====
// Supports 0 to 999,999,999 (ratusan juta)
function numberToIndonesian(num) {
    if (num === 0) return 'nol';
    if (num < 0) return 'minus ' + numberToIndonesian(-num);

    const satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];
    const belasan = ['sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
                     'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'];

    function convert(n) {
        if (n === 0) return '';
        if (n < 10) return satuan[n];
        if (n < 20) return belasan[n - 10];
        if (n < 100) {
            const puluhan = Math.floor(n / 10);
            const sisa = n % 10;
            return satuan[puluhan] + ' puluh' + (sisa ? ' ' + satuan[sisa] : '');
        }
        if (n < 200) {
            const sisa = n % 100;
            return 'seratus' + (sisa ? ' ' + convert(sisa) : '');
        }
        if (n < 1000) {
            const ratusan = Math.floor(n / 100);
            const sisa = n % 100;
            return satuan[ratusan] + ' ratus' + (sisa ? ' ' + convert(sisa) : '');
        }
        if (n < 2000) {
            const sisa = n % 1000;
            return 'seribu' + (sisa ? ' ' + convert(sisa) : '');
        }
        if (n < 1000000) {
            const ribuan = Math.floor(n / 1000);
            const sisa = n % 1000;
            return convert(ribuan) + ' ribu' + (sisa ? ' ' + convert(sisa) : '');
        }
        if (n < 1000000000) {
            const jutaan = Math.floor(n / 1000000);
            const sisa = n % 1000000;
            return convert(jutaan) + ' juta' + (sisa ? ' ' + convert(sisa) : '');
        }
        return String(n); // fallback
    }

    return convert(num);
}

function playNotification() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [523.25, 659.25, 783.99].forEach((freq, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = freq;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.3, ctx.currentTime + i * 0.3);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.3 + 0.8);
            osc.start(ctx.currentTime + i * 0.3);
            osc.stop(ctx.currentTime + i * 0.3 + 0.8);
        });
    } catch(e) {}
}

// ===== SSE Connection =====
let sseFailCount = 0;
function connectSSE() {
    const evtSource = new EventSource(STREAM_URL);

    evtSource.onopen = function() {
        sseFailCount = 0;
        console.log('✅ SSE connected');
    };

    evtSource.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            if (data.success !== false) {
                renderDisplay(data);
            }
        } catch(e) {
            console.error('SSE parse error:', e);
        }
    };

    evtSource.onerror = function() {
        evtSource.close();
        sseFailCount++;
        if (sseFailCount < 3) {
            console.warn('SSE error, retrying in 3s... (attempt ' + sseFailCount + ')');
            setTimeout(connectSSE, 3000);
        } else {
            console.warn('SSE failed 3 times, falling back to polling only');
        }
    };
}

// ===== Fallback: Polling =====
function pollStatus() {
    fetch(STATUS_URL)
        .then(r => r.json())
        .then(data => renderDisplay(data))
        .catch(err => console.error('Poll error:', err));
}

// ===== Initialize =====
if ('speechSynthesis' in window) {
    window.speechSynthesis.getVoices();
    window.speechSynthesis.onvoiceschanged = () => {
        cachedFemaleVoice = null; // Reset cache to re-detect
        getPreferredVoice();
    };
    // Try to get voice immediately (Chrome loads async, Edge loads sync)
    setTimeout(() => getPreferredVoice(), 100);
}

// Try SSE first, fallback to polling
if (typeof(EventSource) !== 'undefined') {
    connectSSE();
} else {
    pollStatus();
    setInterval(pollStatus, 3000);
}

// Backup polling every 5 seconds
setInterval(pollStatus, 5000);
</script>

<style>
@keyframes pulse-row {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
@keyframes pulse-num {
    0% { transform: scale(1); }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); }
}
</style>
