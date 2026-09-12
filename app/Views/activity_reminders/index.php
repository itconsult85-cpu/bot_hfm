<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="card border-0 shadow-sm rounded-4 p-4 mt-2">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h4 class="mb-1 text-primary fw-bold">Evaluasi Aktivitas Member</h4>
            <p class="text-muted mb-0">Member aktif tanpa transaksi sejak bergabung, berdasarkan usia keanggotaan 7–10 hari.</p>
        </div>
        <form method="post" action="<?= base_url('activity-reminders/process') ?>">
            <button class="btn btn-primary rounded-pill px-4" type="submit"><i class="bi bi-send me-1"></i> Proses Pengingat Hari Ini</button>
        </form>
    </div>
    <?php if (session()->getFlashdata('pesan')): ?>
        <div class="alert alert-info"><?= esc(session()->getFlashdata('pesan')) ?></div>
    <?php endif; ?>
    <div class="alert alert-light border small">
        Fase: <strong>H-3</strong> pada hari ke-7, <strong>H-1</strong> pada hari ke-9, dan <strong>hari evaluasi</strong> pada hari ke-10. Pengiriman dicatat sehingga aman dijalankan berulang melalui cron.
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>Nama</th><th>ID Trading</th><th>ID Telegram</th><th>Bergabung</th><th>Terakhir Trading</th><th>Fase</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($members)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada member yang jatuh tempo hari ini.</td></tr>
            <?php else: foreach ($members as $member): ?>
                <tr>
                    <td class="fw-semibold"><?= esc($member['nama'] ?: '-') ?></td>
                    <td><?= esc($member['id_hfm']) ?></td>
                    <td><i class="bi bi-telegram text-info"></i> <?= esc($member['id_telegram']) ?></td>
                    <td><?= esc(date('d M Y', strtotime($member['created_at']))) ?></td>
                    <td><?= empty($member['last_trade']) ? 'Belum Trading' : esc(date('d M Y H:i', strtotime($member['last_trade']))) ?></td>
                    <td><span class="badge <?= $member['reminder_phase'] === 'final' ? 'bg-danger' : ($member['reminder_phase'] === 'h1' ? 'bg-warning text-dark' : 'bg-info text-dark') ?>"><?= esc(strtoupper($member['reminder_phase'])) ?></span></td>
                    <td><a class="btn btn-sm btn-outline-danger rounded-pill" href="<?= base_url('activity-reminders/remove/' . (int) $member['id']) ?>" onclick="return confirm('Keluarkan member dari grup Telegram dan hapus dari database?')"><i class="bi bi-person-x"></i> Keluarkan & Hapus</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
