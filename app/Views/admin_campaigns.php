<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4">

    <!-- Header -->
    <div class="row align-items-md-center justify-content-between mb-4 g-3">
        <div class="col-12 col-md">
            <h4 class="fw-bold tracking-tight text-primary mb-1">
                <i class="bi bi-megaphone me-2"></i><?= esc($title) ?>
            </h4>
            <p class="text-muted small mb-0">Daftar kampanye (Campaign) afiliasi yang terdaftar di akun HFM Anda.</p>
        </div>
        <div class="col-12 col-md-auto">
            <a href="<?= base_url('AdminDashboard/campaigns') ?>" class="btn btn-outline-primary rounded-pill px-4 py-2 small fw-semibold shadow-sm w-100">
                <i class="bi bi-arrow-clockwise me-1"></i> Segarkan Data
            </a>
        </div>
    </div>

    <!-- Notifikasi Error API -->
    <?php if ($debug_api): ?>
        <div class="alert alert-warning small py-2 rounded-3 shadow-sm"><i class="bi bi-exclamation-triangle me-2"></i><?= esc($debug_api) ?></div>
    <?php endif; ?>

    <!-- Summary Card -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="p-3 bg-white border rounded-4 shadow-sm d-flex align-items-center">
                <div class="bg-primary-subtle text-primary rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-collection fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block fw-semibold">Total Campaign</small>
                    <span class="fw-bold fs-4"><?= count($campaigns) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="table-responsive border-0 overflow-hidden shadow-sm rounded-4">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="px-3 py-3 border-bottom-0" style="width: 60px;">No</th>
                    <th class="py-3 border-bottom-0">Nama Kampanye (Campaign Name)</th>
                    <th class="py-3 border-bottom-0 text-center">Campaign ID</th>
                    <th class="py-3 border-bottom-0 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php if (empty($campaigns)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="empty-state">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3">Belum ada Kampanye</h6>
                                <p class="text-muted small">Tidak ada data kampanye yang ditemukan dari API HFM.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1;
                    foreach ($campaigns as $camp): ?>
                        <tr>
                            <td class="px-3 fw-semibold text-muted text-center"><?= $no++ ?></td>
                            <td class="fw-bold text-dark py-3" style="font-size: 0.95rem;">
                                <?= esc($camp['name'] ?? 'Unnamed Campaign') ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-primary border border-primary-subtle px-3 py-2 fs-7 rounded-pill">
                                    <?= esc($camp['id'] ?? $camp['campaign_id'] ?? '-') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php
                                $status = strtolower($camp['status'] ?? 'active');
                                $badgeClass = ($status === 'active') ? 'bg-success' : 'bg-secondary';
                                ?>
                                <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-1">
                                    <?= esc(ucfirst($status)) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>