<?= $this->extend('layout/template') ?>

<?= $this->section('styles') ?>
<style>
    .metric-card,
    .data-card {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .metric-card {
        border-radius: 20px;
    }

    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 25px rgba(0, 0, 0, 0.04);
    }

    .data-card {
        border-radius: 16px;
    }

    .data-card:hover {
        transform: translateX(4px);
        box-shadow: 0 12px 20px rgba(0, 0, 0, 0.03);
        border-color: #cbd5e1;
    }

    .metric-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .avatar-sub {
        width: 46px;
        height: 46px;
        background: #f1f5f9;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #475569;
    }

    .modal-content {
        border-radius: 20px;
        border: none;
    }

    .form-control,
    .form-select {
        border-radius: 10px;
        padding: 0.6rem 1rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-wallet2 text-primary me-2"></i>Detail Wallet Registrations
        </h4>
        <p class="text-muted small">Data registrasi trading account per campaign</p>
    </div>
    <button class="btn btn-outline-primary rounded-pill px-4" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
    </button>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <form method="GET" action="<?= current_url() ?>" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-semibold small">Date From</label>
            <input type="date" name="date_from" class="form-control"
                value="<?= esc($date_from ?? date('Y-m-d', strtotime('-30 days'))) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold small">Date To</label>
            <input type="date" name="date_to" class="form-control"
                value="<?= esc($date_to ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small">Campaign</label>
            <select name="campaign_ids" class="form-select">
                <option value="">All Campaigns</option>
                <?php if (!empty($campaigns)): ?>
                    <?php foreach ($campaigns as $camp): ?>
                        <option value="<?= esc($camp['id']) ?>"
                            <?= ($selected_campaign ?? '') == $camp['id'] ? 'selected' : '' ?>>
                            <?= esc($camp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
        </div>
    </form>
</div>

<?php if (isset($debug_api) && $debug_api): ?>
    <div class="alert alert-warning">
        <strong><i class="bi bi-exclamation-triangle me-1"></i> Debug:</strong>
        <?= esc($debug_api) ?>
    </div>
<?php endif; ?>

<?php if (!empty($wallets)): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stats-card p-3 data-card">
                <p class="text-muted small mb-1"><i class="bi bi-people me-1"></i>Total Registrations</p>
                <h3 class="stat-number text-primary"><?= number_format($summary['total']) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-3 data-card">
                <p class="text-muted small mb-1"><i class="bi bi-check-circle text-success me-1"></i>Active Accounts</p>
                <h3 class="stat-number text-success"><?= number_format($summary['active']) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-3 data-card">
                <p class="text-muted small mb-1"><i class="bi bi-x-circle text-danger me-1"></i>Inactive Accounts</p>
                <h3 class="stat-number text-danger"><?= number_format($summary['inactive']) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-3 data-card">
                <p class="text-muted small mb-1"><i class="bi bi-cash-coin text-warning me-1"></i>Total Deposit</p>
                <h3 class="stat-number text-warning">$<?= number_format($summary['total_deposit'], 2) ?></h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 data-card">
        <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-line me-2"></i>Registrasi per Hari</h6>
        <canvas id="registrationChart" height="80"></canvas>
    </div>

    <div class="table-container data-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-table me-2"></i>Data Registrasi</h6>
            <span class="badge bg-light text-dark"><?= count($wallets) ?> records</span>
        </div>

        <div class="table-responsive">
            <table id="walletTable" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Campaign</th>
                        <th>Account ID</th>
                        <th>Name</th>
                        <th>Currency</th>
                        <th>Deposit</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wallets as $index => $wallet): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                    <?= esc($wallet['campaign_name'] ?? $wallet['campaign_id'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td><code><?= esc($wallet['account_id'] ?? $wallet['id'] ?? '-') ?></code></td>
                            <td><?= esc($wallet['name'] ?? '-') ?></td>
                            <td><span class="badge bg-info bg-opacity-10 text-info"><?= esc($wallet['currency'] ?? 'USD') ?></span></td>
                            <td class="fw-bold">$<?= number_format($wallet['deposit'] ?? 0, 2) ?></td>
                            <td>
                                <?php if (isset($wallet['status']) && $wallet['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($wallet['created_at'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <div class="card p-5 text-center border-0 bg-light rounded-4">
        <i class="bi bi-inbox text-muted fs-1 mb-3"></i>
        <h5 class="fw-bold text-secondary">Tidak ada data wallet</h5>
        <p class="text-muted small">Coba ubah filter tanggal atau campaign</p>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
    const APP_ROUTES = {
        hapusMember: "<?= base_url('AdminDashboard/hapus-member/') ?>",
        syncHfm: "<?= base_url('AdminDashboard/syncHfm/') ?>"
    };

    <?php if (!empty($wallets)): ?>
        $(document).ready(function() {
            $('#walletTable').DataTable({
                pageLength: 25,
                order: [
                    [7, 'desc']
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
                }
            });
        });

        <?php
        $dates = array_keys($summary['by_date']);
        $counts = array_values($summary['by_date']);
        ?>
        const ctx = document.getElementById('registrationChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($dates) ?>,
                datasets: [{
                    label: 'Registrations',
                    data: <?= json_encode($counts) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    <?php endif; ?>
</script>
<?= $this->endSection() ?>