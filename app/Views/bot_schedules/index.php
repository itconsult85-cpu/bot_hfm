<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-primary mb-1">Jadwal Pengiriman Bot</h4>
            <p class="text-muted mb-0">Timezone: <strong>Asia/Jakarta (WIB)</strong>. Perubahan dibaca bot otomatis tanpa edit kode.</p>
        </div>
        <button class="btn btn-warning rounded-pill" onclick="restartTelegramBot()"><i class="bi bi-arrow-clockwise me-1"></i> Restart bot_tele_hfm</button>
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
    if (!confirm('Restart proses PM2 bot_tele_hfm sekarang?')) return;
    try {
        const response = await fetch('<?= base_url('bot-schedules/restart-bot') ?>', {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}});
        const data = await response.json();
        alert(data.message || data.error || 'Selesai');
    } catch (error) { alert('Gagal menghubungi server: ' + error.message); }
}
</script>
<?= $this->endSection() ?>
