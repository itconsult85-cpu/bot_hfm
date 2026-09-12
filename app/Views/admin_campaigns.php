<?= $this->extend('layout/template') ?>

<?= $this->section('styles') ?>
<style>
    .data-card {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        border-radius: 16px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .data-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 20px rgba(0, 0, 0, 0.03);
        border-color: #cbd5e1;
    }

    .border-dashed {
        border: 2px dashed #cbd5e1;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h3 class="fw-bold tracking-tight mb-1">Performa Kampanye (API)</h3>
        <p class="text-muted small">Data performa link IB dari sistem HFM secara realtime.</p>
        <?php if (isset($total_campaigns)): ?>
            <span class="badge bg-primary">Total: <?= $total_campaigns ?> Campaign</span>
        <?php endif; ?>
    </div>
    <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
    </button>
</div>

<?php if (isset($debug_api) && $debug_api): ?>
    <div class="alert alert-warning">
        <strong><i class="bi bi-exclamation-triangle me-1"></i> Debug:</strong> <?= esc($debug_api) ?>
    </div>
<?php endif; ?>

<?php if (isset($http_code) && $http_code !== 200): ?>
    <div class="alert alert-danger">
        <strong>HTTP Status:</strong> <?= $http_code ?>
    </div>
<?php endif; ?>

<?php if (isset($raw_response) && $raw_response && empty($campaigns)): ?>
    <div class="alert alert-info">
        <strong>Raw Response (first 500 chars):</strong>
        <pre class="small" style="max-height:200px; overflow:auto;"><?= esc(substr($raw_response, 0, 500)) ?></pre>
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php if (!empty($campaigns) && is_array($campaigns)): ?>
        <?php foreach ($campaigns as $camp): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card data-card p-4 h-100 border-top border-primary border-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 80%;" title="<?= esc($camp['name'] ?? 'Unnamed') ?>">
                            <i class="bi bi-megaphone-fill text-primary me-2"></i><?= esc($camp['name'] ?? 'Unnamed Campaign') ?>
                        </h6>
                        <span class="badge bg-light text-dark border small"><?= esc($camp['type'] ?? 'Standard') ?></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <p class="text-muted small mb-0"><i class="bi bi-cursor text-secondary me-1"></i> Total Klik</p>
                            <h5 class="fw-bold mb-0"><?= number_format($camp['clicks'] ?? 0) ?></h5>
                        </div>
                        <div class="col-6">
                            <p class="text-muted small mb-0"><i class="bi bi-people text-secondary me-1"></i> Total Akun</p>
                            <h5 class="fw-bold mb-0"><?= number_format($camp['total_trading_account_registrations'] ?? 0) ?></h5>
                        </div>
                        <div class="col-6">
                            <p class="text-muted small mb-0"><i class="bi bi-person-check text-success me-1"></i> Akun Aktif</p>
                            <h5 class="fw-bold text-success mb-0"><?= number_format($camp['active_trading_account_registrations'] ?? 0) ?></h5>
                        </div>
                        <div class="col-6">
                            <p class="text-muted small mb-0"><i class="bi bi-cash-coin text-warning me-1"></i> Komisi ($)</p>
                            <h5 class="fw-bold text-dark mb-0">$<?= number_format($camp['commission'] ?? 0, 2) ?></h5>
                        </div>
                    </div>

                    <?php if (!empty($camp['main_link'])): ?>
                        <div class="mt-4 pt-3 border-top">
                            <p class="small text-muted mb-1 fw-semibold">Link Referal:</p>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control bg-light text-muted" value="<?= esc($camp['main_link']) ?>" readonly id="link-<?= $camp['name'] ?>">
                                <button class="btn btn-outline-secondary" type="button" onclick="salinLink('link-<?= $camp['name'] ?>')">
                                    <i class="bi bi-clipboard"></i> Salin
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card p-5 text-center border-dashed bg-light rounded-4">
                <i class="bi bi-inbox text-muted fs-1 mb-3"></i>
                <h5 class="fw-bold text-secondary">Tidak ada data Kampanye</h5>
                <p class="text-muted small mb-0">Pastikan API Key benar, HFM tidak gangguan, atau Anda memang belum membuat kampanye.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const APP_ROUTES = {
        hapusMember: "<?= base_url('AdminDashboard/hapus-member/') ?>",
        syncHfm: "<?= base_url('AdminDashboard/syncHfm/') ?>"
    };

    function salinLink(inputId) {
        var copyText = document.getElementById(inputId);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value).then(() => {
            alert("Link berhasil disalin!");
        });
    }
</script>
<?= $this->endSection() ?>