<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-plus text-warning me-2"></i><?= esc($title ?? 'Anggota Telegram Belum Tercatat') ?></h4>
        <p class="text-muted small mb-0">Data dibaca langsung dari grup Telegram melalui MTProto dan dibandingkan dengan database. Halaman ini tidak mengubah grup maupun database.</p>
    </div>
    <a class="btn btn-outline-secondary rounded-pill" href="<?= base_url('mtproto-group-members') ?>"><i class="bi bi-arrow-clockwise me-1"></i>Scan ulang</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-warning rounded-4 shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc($error) ?>
        <div class="small mt-2">Pastikan bot sudah direstart setelah konfigurasi MTProto di <code>.env</code>.</div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Grup yang dipindai</div><div class="fw-bold"><?= esc($groupName ?: 'Belum tersedia') ?></div><code class="small"><?= esc($groupId ?: '-') ?></code></div></div>
    <div class="col-6 col-md-4"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Total anggota terbaca</div><div class="fs-4 fw-bold text-primary"><?= number_format((int) ($scannedTotal ?? 0)) ?></div></div></div>
    <div class="col-6 col-md-4"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Belum tercatat</div><div class="fs-4 fw-bold text-warning"><?= number_format((int) ($unrecordedTotal ?? 0)) ?></div></div></div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4">
    <form method="get" action="<?= base_url('mtproto-group-members') ?>" class="row g-2 align-items-end">
        <div class="col-12 col-md-9">
            <label for="search" class="form-label small fw-semibold">Cari ID, username, atau nama</label>
            <input type="search" id="search" name="search" class="form-control" value="<?= esc($search ?? '') ?>" placeholder="contoh: 123456789 atau username">
        </div>
        <div class="col-12 col-md-3"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Filter hasil</button></div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Kandidat belum tercatat</h6>
        <span class="badge text-bg-warning"><?= number_format(count($members ?? [])) ?> data</span>
    </div>
    <div class="alert alert-info small rounded-3"><i class="bi bi-shield-check me-1"></i>Mode read-only. Tidak ada operasi hapus, kick, ban, atau penyimpanan otomatis dari halaman ini.</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Nama</th><th>Username</th><th>ID Telegram</th><th>Jenis</th></tr></thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-5">Tidak ditemukan anggota grup yang belum tercatat.</td></tr>
                <?php else: ?>
                    <?php foreach ($members as $member): ?>
                        <?php $name = trim(implode(' ', array_filter([$member['first_name'] ?? '', $member['last_name'] ?? '']))); ?>
                        <tr>
                            <td class="fw-semibold"><?= esc($name ?: 'Tanpa nama') ?></td>
                            <td><?= !empty($member['username']) ? '@' . esc($member['username']) : '<span class="text-muted">—</span>' ?></td>
                            <td><code><?= esc($member['telegram_id']) ?></code></td>
                            <td>
                                <?php if (!empty($member['is_deleted'])): ?><span class="badge bg-secondary">Akun dihapus</span>
                                <?php elseif (!empty($member['is_bot'])): ?><span class="badge bg-info text-dark">Bot</span>
                                <?php else: ?><span class="badge bg-success-subtle text-success">User</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
