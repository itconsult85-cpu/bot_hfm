<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-list-check text-primary me-2"></i><?= esc($title ?? 'Progress Pendaftar Bot') ?></h4>
        <p class="text-muted small mb-0">Pantau tahap terakhir pendaftaran dan aktivitas pengguna Telegram.</p>
    </div>
</div>

<?php if ($successMessage = session()->getFlashdata('success')): ?>
    <div class="alert alert-success rounded-4" role="status"><?= esc($successMessage) ?></div>
<?php endif; ?>
<?php if ($errorMessage = session()->getFlashdata('error')): ?>
    <div class="alert alert-danger rounded-4" role="alert"><?= esc($errorMessage) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4">
    <form method="get" action="<?= base_url('user-progress') ?>" class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
            <label class="form-label small fw-semibold" for="search">Cari nama, ID Telegram, atau nomor</label>
            <input class="form-control" id="search" name="search" value="<?= esc($search ?? '') ?>" placeholder="Nama / ID Telegram / nomor">
        </div>
        <div class="col-8 col-md-3">
            <label class="form-label small fw-semibold" for="step">Tahap</label>
            <select class="form-select" id="step" name="step">
                <option value="">Semua tahap</option>
                <?php for ($i = 1; $i <= 7; $i++): ?>
                    <option value="<?= $i ?>" <?= (string) ($step_filter ?? '') === (string) $i ? 'selected' : '' ?>>Tahap <?= $i ?><?= $i === 7 ? ' — selesai/aktif' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-4 col-md-3 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
            <a class="btn btn-outline-secondary" href="<?= base_url('user-progress') ?>" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4">
    <p class="text-muted small mb-3">Reset mengembalikan progres bot ke Tahap 1 dan membersihkan state sementara. Riwayat chat serta status VIP/keanggotaan grup tidak dihapus.</p>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Pengguna yang tercatat</h6>
        <span class="badge text-bg-light"><?= number_format((int) ($total ?? 0)) ?> pengguna</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Pengguna</th><th>ID Telegram</th><th>Tahap</th><th>Mulai</th><th>Aktif terakhir</th><th>Status</th><th>Tindakan</th></tr>
            </thead>
            <tbody>
                <?php if (empty($progress)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">Belum ada progres pendaftar yang tercatat. Setelah bot menerima pesan atau `/start`, data akan muncul di sini.</td></tr>
                <?php else: ?>
                    <?php foreach ($progress as $row): ?>
                        <?php $stepNumber = (int) ($row['current_step'] ?? 0); $done = $stepNumber >= 7 || !empty($row['completed_at']); ?>
                        <tr>
                            <td class="fw-semibold"><?= esc($row['user_name'] ?: 'Pengguna') ?></td>
                            <td><code><?= esc($row['user_id']) ?></code></td>
                            <td><span class="badge <?= $done ? 'text-bg-success' : 'text-bg-primary' ?>">Tahap <?= $stepNumber ?></span></td>
                            <td class="small text-muted"><?= !empty($row['started_at']) ? esc(date('d M Y H:i', strtotime($row['started_at']))) : '—' ?></td>
                            <td class="small text-muted"><?= !empty($row['last_active']) ? esc(date('d M Y H:i', strtotime($row['last_active']))) : '—' ?></td>
                            <td><?= $done ? '<span class="badge bg-success-subtle text-success">Selesai / aktif</span>' : '<span class="badge bg-warning-subtle text-warning-emphasis">Dalam proses</span>' ?></td>
                            <td>
                                <form method="post" action="<?= base_url('user-progress/reset') ?>" onsubmit="return confirm('Reset progres pengguna ini ke Tahap 1? Status VIP/grup tidak akan berubah.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= esc($row['user_id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger text-nowrap" type="submit" title="Reset progres ke Tahap 1">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $totalProgress = (int) ($total ?? 0); $fromProgress = $totalProgress > 0 ? (($currentPage - 1) * $perPage + 1) : 0; $toProgress = min($currentPage * $perPage, $totalProgress); ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-4">
        <span class="text-muted small">
            <?php if ($totalProgress > 0): ?>Menampilkan <b><?= $fromProgress ?></b> hingga <b><?= $toProgress ?></b> dari <b><?= number_format($totalProgress) ?></b> data<?php else: ?>Menampilkan 0 data<?php endif; ?>
        </span>
        <?= $pager->links() ?>
    </div>
</div>
<?= $this->endSection() ?>
