<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-people text-primary me-2"></i><?= esc($title ?? 'Keanggotaan Grup') ?></h4>
        <p class="text-muted small mb-0">Satu baris mewakili satu keanggotaan grup; pengguna yang tercatat di Telegram dan WhatsApp akan tampil pada dua baris.</p>
    </div>
</div>

<?php if (!empty($setupError)): ?>
    <div class="alert alert-warning"><?= esc($setupError) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4">
    <form method="get" action="<?= base_url('group-memberships') ?>" class="row g-2 align-items-end">
        <div class="col-12 col-lg-5">
            <label class="form-label small fw-semibold" for="search">Cari pengguna atau grup</label>
            <input class="form-control" id="search" name="search" value="<?= esc($search ?? '') ?>" placeholder="ID, nama, nomor, atau nama grup">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label small fw-semibold" for="platform">Platform</label>
            <select class="form-select" id="platform" name="platform">
                <option value="">Semua</option>
                <option value="telegram" <?= ($platform_filter ?? '') === 'telegram' ? 'selected' : '' ?>>Telegram</option>
                <option value="whatsapp" <?= ($platform_filter ?? '') === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label small fw-semibold" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Semua</option>
                <option value="active" <?= ($status_filter ?? '') === 'active' ? 'selected' : '' ?>>Masih bergabung</option>
                <option value="left" <?= ($status_filter ?? '') === 'left' ? 'selected' : '' ?>>Sudah keluar</option>
            </select>
        </div>
        <div class="col-8 col-lg-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Filter</button></div>
        <div class="col-4 col-lg-1"><a class="btn btn-outline-secondary w-100" href="<?= base_url('group-memberships') ?>" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a></div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Riwayat keanggotaan</h6>
        <span class="badge text-bg-light"><?= (int) ($total ?? 0) ?> catatan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Pengguna</th><th>Platform</th><th>Grup</th><th>Status</th><th>Waktu masuk</th><th>Waktu keluar</th></tr></thead>
            <tbody>
                <?php if (empty($memberships)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada event keanggotaan grup yang tercatat.</td></tr>
                <?php else: ?>
                    <?php foreach ($memberships as $membership): ?>
                        <?php $isTelegram = $membership['platform'] === 'telegram'; ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= esc($membership['display_name'] ?: 'Pengguna') ?></div>
                                <?php if (!empty($membership['phone_number'])): ?><div class="small text-muted"><?= esc($membership['phone_number']) ?></div><?php endif; ?>
                                <code class="small"><?= esc($membership['user_id']) ?></code>
                            </td>
                            <td><span class="badge <?= $isTelegram ? 'bg-info text-dark' : 'bg-success' ?>"><i class="bi <?= $isTelegram ? 'bi-telegram' : 'bi-whatsapp' ?> me-1"></i><?= $isTelegram ? 'Telegram' : 'WhatsApp' ?></span></td>
                            <td><div class="fw-semibold"><?= esc($membership['group_name'] ?: '—') ?></div><code class="small text-muted"><?= esc($membership['group_id']) ?></code></td>
                            <td><?= $membership['status'] === 'active' ? '<span class="badge bg-success-subtle text-success">Masih bergabung</span>' : '<span class="badge bg-secondary-subtle text-secondary">Sudah keluar</span>' ?></td>
                            <td class="small text-muted"><?= !empty($membership['joined_at']) ? esc(date('d M Y H:i', strtotime($membership['joined_at']))) : '—' ?></td>
                            <td class="small text-muted"><?= !empty($membership['left_at']) ? esc(date('d M Y H:i', strtotime($membership['left_at']))) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $totalMemberships = (int) ($total ?? 0); $fromMemberships = $totalMemberships > 0 ? (($currentPage - 1) * $perPage + 1) : 0; $toMemberships = min($currentPage * $perPage, $totalMemberships); ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-4">
        <span class="text-muted small">
            <?php if ($totalMemberships > 0): ?>Menampilkan <b><?= $fromMemberships ?></b> hingga <b><?= $toMemberships ?></b> dari <b><?= number_format($totalMemberships) ?></b> data<?php else: ?>Menampilkan 0 data<?php endif; ?>
        </span>
        <?= $pagerLinks ?? '' ?>
    </div>
</div>
<?= $this->endSection() ?>
