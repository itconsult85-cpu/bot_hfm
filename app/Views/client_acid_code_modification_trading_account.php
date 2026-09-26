<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-badge text-primary me-2"></i>Modifikasi ACID Trading Account</h4>
        <p class="text-muted small mb-0">Mengubah ACID code trading account melalui API HFM tanpa mengubah data lokal.</p>
    </div>
</div>

<?php if ($message = session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= esc($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>
<?php if ($message = session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= esc($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold mb-1">Kirim Perubahan ACID</h5>
            <p class="text-muted small mb-4">Pastikan trading account ID dan ACID code sudah benar sebelum mengirim.</p>
            <form method="post" action="<?= base_url('client-acid-code-modification/trading-account/modify') ?>" onsubmit="return window.confirm('Perubahan ACID trading account akan dikirim ke API HFM. Lanjutkan?');">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="member-picker" class="form-label fw-semibold">Pilih member (opsional)</label>
                    <select id="member-picker" class="form-select">
                        <option value="">-- Pilih member untuk mengisi ID referensi --</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?= esc($member['id_hfm']) ?>">
                                <?= esc(($member['nama'] ?: 'Tanpa nama') . ' — ' . $member['id_hfm'] . ' (' . ($member['status'] ?? '-') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">ID HFM pada daftar member hanya sebagai referensi; pastikan ID trading account sesuai data API HFM.</div>
                </div>
                <div class="mb-3">
                    <label for="client_trading_account_id" class="form-label fw-semibold">Client trading account ID <span class="text-danger">*</span></label>
                    <input type="text" name="client_trading_account_id" id="client_trading_account_id" class="form-control" inputmode="numeric" pattern="[1-9][0-9]*" maxlength="30" required value="<?= esc(old('client_trading_account_id', $oldTradingAccountId)) ?>" placeholder="Masukkan ID trading account">
                </div>
                <div class="mb-4">
                    <label for="acid" class="form-label fw-semibold">ACID code baru <span class="text-danger">*</span></label>
                    <input type="text" name="acid" id="acid" class="form-control" maxlength="100" required value="<?= esc(old('acid')) ?>" placeholder="Masukkan ACID code">
                </div>
                <button type="submit" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-send me-1"></i> Kirim ke API HFM
                </button>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-light">
            <h6 class="fw-bold"><i class="bi bi-info-circle text-primary me-2"></i>Cara penggunaan</h6>
            <ol class="small text-secondary ps-3 mb-3">
                <li>Pilih member sebagai referensi, atau isi client trading account ID manual.</li>
                <li>Pastikan ID yang digunakan adalah ID trading account, bukan wallet ID.</li>
                <li>Masukkan ACID code baru, lalu klik <strong>Kirim ke API HFM</strong>.</li>
                <li>Konfirmasi dan baca notifikasi hasil dari API.</li>
            </ol>
            <div class="alert alert-warning small mb-0">
                <i class="bi bi-shield-exclamation me-1"></i>
                Fitur ini melakukan perubahan langsung pada trading account di HFM. Pastikan ID dan kode sudah diverifikasi.
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
    document.getElementById('member-picker')?.addEventListener('change', function () {
        document.getElementById('client_trading_account_id').value = this.value;
    });
</script>
<?= $this->endSection() ?>
