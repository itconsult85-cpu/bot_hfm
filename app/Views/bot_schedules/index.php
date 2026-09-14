<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-primary mb-1">Jadwal Pengiriman Bot</h4>
            <p class="text-muted mb-0">Timezone: <strong>Asia/Jakarta (WIB)</strong>. Perubahan dibaca bot otomatis tanpa edit kode.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary rounded-pill" onclick="sendDailyReport()"><i class="bi bi-send me-1"></i> Kirim Report Sekarang</button>
            <button class="btn btn-warning rounded-pill" onclick="restartTelegramBot()"><i class="bi bi-arrow-clockwise me-1"></i> Restart bot_tele_hfm</button>
        </div>
    </div>
    <?php if (session()->getFlashdata('pesan')): ?><div class="alert alert-info"><?= esc(session()->getFlashdata('pesan')) ?></div><?php endif; ?>
    <div class="alert alert-light border small">Laporan harian dan evaluasi aktivitas memiliki jadwal terpisah. Menonaktifkan jadwal hanya menghentikan proses tersebut, tidak menghapus fase atau data member.</div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light"><tr><th>Proses</th><th>Jenis</th><th>Jam WIB</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($schedules as $schedule): ?>
                <tr>
                    <td class="fw-semibold"><?= esc($schedule['schedule_name']) ?></td>
                    <td><span class="badge bg-secondary"><?= esc($schedule['schedule_type']) ?></span></td>
                    <td><form class="d-flex gap-2" method="post" action="<?= base_url('bot-schedules/update/' . (int) $schedule['id']) ?>">
                        <?= csrf_field() ?><input class="form-control" type="time" name="run_at" value="<?= esc($schedule['run_at']) ?>" required></td>
                    <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= $schedule['is_active'] ? 'checked' : '' ?>><label class="form-check-label"><?= $schedule['is_active'] ? 'Aktif' : 'Nonaktif' ?></label></div></td>
                    <td><button class="btn btn-primary btn-sm rounded-pill" type="submit"><i class="bi bi-save me-1"></i>Simpan</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
async function restartTelegramBot() {
    const approved = await showAppConfirm('Restart proses PM2 bot_tele_hfm sekarang?', {
        title: 'Restart bot Telegram',
        confirmText: 'Restart sekarang'
    });
    if (!approved) return;
    try {
        const response = await fetch('<?= base_url('bot-schedules/restart-bot') ?>', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                'Accept': 'application/json'
            }
        });
        const raw = await response.text();
        let data;
        try { data = JSON.parse(raw); } catch (_) { data = { error: raw.substring(0, 500) }; }
        if (!response.ok) {
            const detail = data.detail ? `\nDetail: ${data.detail}` : '';
            throw new Error((data.error || `HTTP ${response.status}`) + detail);
        }
        await showAppAlert(data.message || 'bot_tele_hfm berhasil direstart.');
    } catch (error) { await showAppAlert('Restart gagal: ' + error.message); }
}

async function sendDailyReport() {
    const approved = await showAppConfirm('Kirim report harian berdasarkan data yang tersimpan di database sekarang?', {
        title: 'Kirim report manual',
        confirmText: 'Kirim sekarang'
    });
    if (!approved) return;
    try {
        const response = await fetch('<?= base_url('bot-schedules/send-report') ?>', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                'Accept': 'application/json'
            }
        });
        const raw = await response.text();
        let data;
        try { data = JSON.parse(raw); } catch (_) { data = { error: raw.substring(0, 500) }; }
        if (!response.ok) throw new Error(data.error || `HTTP ${response.status}`);
        await showAppAlert(data.message || 'Report harian berhasil dikirim.');
    } catch (error) {
        await showAppAlert('Pengiriman report gagal: ' + error.message);
    }
}
</script>
<?= $this->endSection() ?>
