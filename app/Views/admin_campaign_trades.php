<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold tracking-tight text-primary mb-1">
                <i class="bi bi-currency-exchange me-2"></i><?= esc($title) ?>
            </h4>
            <p class="text-muted small mb-0">Daftar transaksi akun yang menghasilkan komisi berdasarkan Kampanye.</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-light p-3 rounded-4 border mb-4">
        <form method="GET" action="<?= base_url('AdminDashboard/campaignTrades') ?>" class="row g-2 align-items-end">
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
    <?php if ($total_semua_trade > 500): ?>
        <div class="alert alert-info py-2 small shadow-sm rounded-3">
            <i class="bi bi-info-circle-fill me-2"></i> Menampilkan <strong>500</strong> transaksi pertama dari total <strong><?= number_format($total_semua_trade) ?></strong> transaksi. Gunakan rentang tanggal yang lebih sempit untuk melihat data spesifik.
        </div>
    <?php elseif ($total_semua_trade > 0): ?>
        <div class="alert alert-success py-2 small shadow-sm rounded-3">
            <i class="bi bi-check-circle-fill me-2"></i> Total <strong><?= number_format($total_semua_trade) ?></strong> transaksi ditemukan.
        </div>
    <?php endif; ?>

    <!-- Data Table -->
    <div class="table-responsive border-0 overflow-hidden shadow-sm rounded-4">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="px-3 py-2 border-bottom-0">Order ID</th>
                    <th class="py-2 border-bottom-0">Account ID</th>
                    <th class="py-2 border-bottom-0">Campaign / Symbol</th>
                    <th class="py-2 text-end border-bottom-0">Volume</th>
                    <th class="py-2 text-end border-bottom-0">Commission</th>
                    <th class="py-2 text-center border-bottom-0 px-3">Waktu (Time)</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php if (empty($trades)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">Belum ada komisi transaksi yang dihasilkan untuk filter ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trades as $trade): ?>
                        <tr>
                            <td class="px-3 fw-bold text-dark" style="font-size: 0.9rem;">
                                <?= esc($trade['order_id'] ?? $trade['trade_id'] ?? '-') ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border border-primary-subtle fs-7 px-2 py-1">
                                    <?= esc($trade['account_id'] ?? '-') ?>
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold text-secondary d-block"><?= esc($trade['symbol'] ?? '-') ?></span>
                                <span class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-megaphone me-1"></i><?= esc($trade['campaign'] ?? 'Default') ?></span>
                            </td>
                            <td class="text-end text-primary fw-semibold" style="font-size: 0.9rem;">
                                <?= number_format($trade['volume'] ?? 0, 2) ?>
                            </td>
                            <td class="text-end text-warning fw-semibold" style="font-size: 0.9rem;">
                                $<?= number_format($trade['commission'] ?? 0, 2) ?>
                            </td>
                            <td class="text-center px-3 text-muted" style="font-size: 0.8rem;">
                                <?= esc($trade['close_time'] ?? $trade['created_at'] ?? $trade['open_time'] ?? 'N/A') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>