<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold tracking-tight text-primary mb-1">
                <i class="bi bi-list-columns-reverse me-2"></i><?= esc($title) ?>
            </h4>
            <p class="text-muted small mb-0">Laporan detail riwayat transaksi (trades) klien dari API HFM.</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-light p-3 rounded-4 border mb-4">
        <form method="GET" action="<?= base_url('AdminDashboard/clientTrades') ?>" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted fw-semibold">Dari Tanggal</label>
                <input type="date" class="form-control form-control-sm rounded-3" name="from_date" value="<?= esc($date_from) ?>" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted fw-semibold">Sampai Tanggal</label>
                <input type="date" class="form-control form-control-sm rounded-3" name="to_date" value="<?= esc($date_to) ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted fw-semibold">Platform</label>
                <select class="form-select form-select-sm rounded-3" name="platform">
                    <option value="">Semua Platform</option>
                    <option value="MT4" <?= $platform == 'MT4' ? 'selected' : '' ?>>MT4</option>
                    <option value="MT5" <?= $platform == 'MT5' ? 'selected' : '' ?>>MT5</option>
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

    <!-- Summary (Totals) Cards -->
    <?php if (!empty($totals)): ?>
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="p-3 bg-white border rounded-4 shadow-sm text-center">
                    <small class="text-muted d-block fw-semibold">Total Trades</small>
                    <span class="fw-bold fs-5"><?= number_format($totals['trades'] ?? 0) ?></span>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-3 bg-white border rounded-4 shadow-sm text-center">
                    <small class="text-muted d-block fw-semibold">Total Volume</small>
                    <span class="fw-bold text-primary fs-5"><?= number_format($totals['volume'] ?? 0, 2) ?></span>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-3 bg-white border rounded-4 shadow-sm text-center">
                    <small class="text-muted d-block fw-semibold">Total Commission</small>
                    <span class="fw-bold text-warning fs-5">$<?= number_format($totals['commission'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Peringatan Batas Data -->
    <?php if ($total_semua_trade > 500): ?>
        <div class="alert alert-info py-2 small shadow-sm rounded-3">
            <i class="bi bi-info-circle-fill me-2"></i> Menampilkan <strong>500</strong> transaksi pertama dari total <strong><?= number_format($total_semua_trade) ?></strong> transaksi. Gunakan rentang tanggal yang lebih sempit untuk melihat data secara spesifik.
        </div>
    <?php endif; ?>

    <!-- Data Table -->
    <!-- Data Table -->
    <div class="table-responsive border-0 shadow-sm rounded-4 text-nowrap">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="px-3 py-2 border-bottom-0">Order ID</th>
                    <!-- Kolom berikut disembunyikan di versi mobile (HP) -->
                    <th class="py-2 border-bottom-0 d-none d-md-table-cell">Account ID</th>
                    <th class="py-2 border-bottom-0 d-none d-md-table-cell">Symbol</th>
                    <th class="py-2 text-end border-bottom-0 d-none d-md-table-cell">Volume</th>
                    <th class="py-2 text-end border-bottom-0 px-3 px-md-2">Commission</th>
                    <th class="py-2 text-center border-bottom-0 px-3 d-none d-md-table-cell">Open & Close Time</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php if (empty($trades)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">Belum ada data transaksi untuk rentang waktu ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trades as $index => $trade): ?> <!-- Menambahkan $index untuk target collapse -->

                        <!-- Baris Utama -->
                        <tr>
                            <td class="px-3 fw-bold text-dark" style="font-size: 0.9rem;">
                                <!-- Tombol Expand/Collapse khusus HP -->
                                <button class="btn btn-sm btn-light border rounded-circle d-md-none shadow-sm fw-bold p-1 d-inline-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; line-height: 1;" type="button" data-bs-toggle="collapse" data-bs-target="#expandTrade<?= $index ?>" aria-expanded="false">
                                    <i class="bi bi-chevron-down text-primary" style="font-size: 0.75rem;"></i>
                                </button>
                                <?= esc($trade['order_id']) ?>
                            </td>

                            <!-- Kolom Account ID (PC) -->
                            <td class="d-none d-md-table-cell">
                                <span class="badge bg-light text-primary border border-primary-subtle fs-7 px-2 py-1">
                                    <?= esc($trade['account_id']) ?>
                                </span>
                            </td>

                            <!-- Kolom Symbol (PC) -->
                            <td class="fw-semibold text-secondary d-none d-md-table-cell">
                                <?= esc($trade['symbol']) ?>
                            </td>

                            <!-- Kolom Volume (PC) -->
                            <td class="text-end text-primary fw-semibold d-none d-md-table-cell" style="font-size: 0.9rem;">
                                <?= number_format($trade['volume'] ?? 0, 2) ?>
                            </td>

                            <!-- Kolom Komisi (PC & HP) -->
                            <td class="text-end text-warning fw-semibold px-3 px-md-2" style="font-size: 0.9rem;">
                                $<?= number_format($trade['commission'] ?? 0, 2) ?>
                            </td>

                            <!-- Kolom Waktu (PC) -->
                            <td class="text-center px-3 d-none d-md-table-cell" style="font-size: 0.8rem;">
                                <div class="text-muted mb-1">
                                    <span class="fw-semibold">Open:</span> <?= esc($trade['open_time']) ?>
                                </div>
                                <div class="text-muted">
                                    <span class="fw-semibold">Close:</span> <?= esc($trade['close_time'] ?: 'N/A') ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Baris Collapse (Muncul di HP saat tombol ditekan) -->
                        <tr id="expandTrade<?= $index ?>" class="collapse d-md-none bg-light border-bottom">
                            <!-- colspan="2" karena hanya ada 2 kolom utama yang terlihat di HP -->
                            <td colspan="2" class="px-4 py-3 shadow-inner">
                                <div class="row g-3 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Account ID</span>
                                        <span class="badge bg-white text-primary border border-primary-subtle px-2 py-1">
                                            <?= esc($trade['account_id']) ?>
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Symbol</span>
                                        <span class="fw-semibold text-dark"><?= esc($trade['symbol']) ?></span>
                                    </div>
                                    <div class="col-12">
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Volume</span>
                                        <span class="fw-bold text-primary"><?= number_format($trade['volume'] ?? 0, 2) ?></span>
                                    </div>
                                    <div class="col-12 border-top pt-2 mt-1">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted" style="font-size: 0.75rem;">Open:</span>
                                            <span class="text-dark fw-semibold"><i class="bi bi-clock me-1"></i><?= esc($trade['open_time']) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted" style="font-size: 0.75rem;">Close:</span>
                                            <span class="text-dark"><i class="bi bi-clock-history me-1"></i><?= esc($trade['close_time'] ?: 'N/A') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>