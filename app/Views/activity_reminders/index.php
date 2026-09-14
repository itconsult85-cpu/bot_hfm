<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="card border-0 shadow-sm rounded-4 p-4 mt-2">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h4 class="mb-1 text-primary fw-bold">Evaluasi Aktivitas Member</h4>
            <p class="text-muted mb-0">Member aktif tanpa transaksi sejak bergabung, berdasarkan usia keanggotaan 7–10 hari.</p>
        </div>
        <span class="badge bg-success rounded-pill px-3 py-2"><i class="bi bi-cloud-check me-1"></i> Status dari API HFM</span>
    </div>
    <?php if (session()->getFlashdata('pesan')): ?>
        <div class="alert alert-info"><?= esc(session()->getFlashdata('pesan')) ?></div>
    <?php endif; ?>
    <div class="alert alert-light border small">
        Fase: <strong>H-3</strong> pada hari ke-7, <strong>H-1</strong> pada hari ke-9, dan <strong>hari evaluasi</strong> pada hari ke-10. Status trading diambil langsung dari API HFM. Tidak ada kick atau penghapusan otomatis; tindakan hanya berjalan setelah admin menekan tombol.
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>No</th><th>Nama</th><th>ID Trading</th><th>ID Telegram</th><th>Bergabung</th><th>Terakhir Trading API HFM</th><th>Fase</th><th>Aksi Admin</th></tr></thead>
            <tbody>
            <?php if (empty($members)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada member yang jatuh tempo hari ini.</td></tr>
            <?php else: $no = 1; foreach ($members as $member): ?>
                <tr>
                    <td><?= $no++ ?></td><td class="fw-semibold"><?= esc($member['nama'] ?: '-') ?></td>
                    <td><?= esc($member['id_hfm']) ?></td>
                    <td><i class="bi bi-telegram text-info"></i> <?= esc($member['id_telegram']) ?></td>
                    <td><?= esc(date('d M Y', strtotime($member['created_at']))) ?></td>
                    <td><?= esc($member['last_trade_display']) ?></td>
                    <td><span class="badge <?= $member['reminder_phase'] === 'final' ? 'bg-danger' : ($member['reminder_phase'] === 'h1' ? 'bg-warning text-dark' : 'bg-info text-dark') ?>"><?= esc(strtoupper($member['reminder_phase'])) ?></span></td>
                    <td><a class="btn btn-sm btn-outline-danger rounded-pill" href="<?= base_url('activity-reminders/remove/' . (int) $member['id']) ?>" data-confirm="Kick client dari Telegram dan hapus data database?" data-confirm-title="Kick dan hapus client" data-confirm-button="Ya, kick & hapus"><i class="bi bi-person-x"></i> Kick & Hapus</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
