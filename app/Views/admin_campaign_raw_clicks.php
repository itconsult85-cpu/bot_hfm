<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold tracking-tight text-primary mb-1">
                <i class="bi bi-cursor me-2"></i><?= esc($title) ?>
            </h4>
            <p class="text-muted small mb-0">Laporan detail klik mentah yang dihasilkan oleh tautan afiliasi Anda.</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-light p-3 rounded-4 border mb-4">
        <form method="GET" action="<?= base_url('AdminDashboard/campaignRawClicks') ?>" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted fw-semibold">Dari Tanggal</label>
                <input type="date" class="form-control form-control-sm rounded-3" name="date_from" value="<?= esc($date_from) ?>" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted fw-semibold">Sampai Tanggal</label>
                <input type="date" class="form-control form-control-sm rounded-3" name="date_to" value="<?= esc($date_to) ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted fw-semibold">Kampanye (Campaign)</label>
                <select class="form-select form-select-sm rounded-3" name="campaign_ids">
                    <option value="">Semua Kampanye</option>
                    <?php foreach ($campaigns as $camp): ?>
                        <option value="<?= esc($camp['id']) ?>" <?= $selected_campaign == $camp['id'] ? 'selected' : '' ?>>
                            <?= esc($camp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold rounded-pill py-2 shadow-sm">
                    <i class="bi bi-filter"></i> Cari Data
                </button>
            </div>
        </form>
    </div>

    <!-- Notifikasi Error API -->
    <?php if ($debug_api): ?>
        <div class="alert alert-warning small py-2 rounded-3 shadow-sm"><i class="bi bi-exclamation-triangle me-2"></i><?= esc($debug_api) ?></div>
    <?php endif; ?>

    <!-- Peringatan Batas Data -->
    <?php if ($total_semua_klik > 500): ?>
        <div class="alert alert-info py-2 small shadow-sm rounded-3">
            <i class="bi bi-info-circle-fill me-2"></i> Menampilkan <strong>500</strong> klik pertama dari total <strong><?= number_format($total_semua_klik) ?></strong> klik. Gunakan rentang tanggal yang lebih sempit untuk melihat data spesifik.
        </div>
    <?php elseif ($total_semua_klik > 0): ?>
        <div class="alert alert-success py-2 small shadow-sm rounded-3">
            <i class="bi bi-check-circle-fill me-2"></i> Total <strong><?= number_format($total_semua_klik) ?></strong> klik ditemukan.
        </div>
    <?php endif; ?>

    <!-- Data Table -->
    <div class="table-responsive border-0 overflow-hidden shadow-sm rounded-4">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="px-3 py-2 border-bottom-0" style="width: 50px;">No</th>
                    <th class="py-2 border-bottom-0">Alamat IP & Lokasi</th>
                    <th class="py-2 border-bottom-0">Kampanye (Campaign)</th>
                    <th class="py-2 border-bottom-0">Sumber (Referrer)</th>
                    <th class="py-2 text-center border-bottom-0 px-3">Waktu Klik</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php if (empty($clicks)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">Belum ada data klik untuk filter ini.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1;
                    foreach ($clicks as $click): ?>
                        <tr>
                            <td class="px-3 text-muted fw-semibold"><?= $no++ ?></td>
                            <td>
                                <span class="fw-bold text-dark d-block" style="font-size: 0.9rem;">
                                    <?= esc($click['ip_address'] ?? $click['ip'] ?? 'Unknown IP') ?>
                                </span>
                                <span class="text-muted small" style="font-size: 0.75rem;">
                                    <i class="bi bi-geo-alt me-1"></i><?= esc($click['country'] ?? 'Unknown Location') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border border-primary-subtle fs-7 px-2 py-1">
                                    <?= esc($click['campaign'] ?? $click['campaign_name'] ?? 'Default') ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-secondary small text-truncate" style="max-width: 200px;" title="<?= esc($click['referer'] ?? $click['source'] ?? 'Direct / None') ?>">
                                    <?= esc($click['referer'] ?? $click['source'] ?? 'Direct / None') ?>
                                </div>
                            </td>
                            <td class="text-center px-3 text-muted" style="font-size: 0.8rem;">
                                <?= esc($click['created_at'] ?? $click['click_time'] ?? $click['date'] ?? 'N/A') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>