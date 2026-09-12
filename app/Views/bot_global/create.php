<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<div class="container-fluid mt-3">
    <div class="mb-4">
        <h3 class="fw-bold tracking-tight mb-1">
            <i class="bi bi-code-square text-primary me-2"></i><?= esc($title ?? 'Form Variabel Global') ?>
        </h3>
        <p class="text-muted small">Kelola variabel teks dinamis yang akan dipanggil oleh bot Telegram.</p>
    </div>

    <div class="card shadow-sm border-0 rounded-4 p-4 mx-auto" style="max-width: 700px;">
        <form action="<?= isset($global) ? base_url('bot-global/update/' . $global['id']) : base_url('bot-global/store') ?>" method="POST">

            <div class="mb-3">
                <label class="form-label fw-semibold text-muted small">Nama Key (Tanpa Spasi)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                    <input type="text" name="key_name" class="form-control border-start-0 ps-0 rounded-end-3"
                        value="<?= isset($global) ? esc($global['key_name']) : '' ?>"
                        placeholder="Contoh: LINK_DAFTAR" required>
                </div>
                <div class="form-text" style="font-size: 0.75rem;">Gunakan huruf kapital dan *underscore* (_).</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold text-muted small">Isi Nilai (Value)</label>
                <textarea name="key_value" class="form-control rounded-3" rows="5"
                    placeholder="Masukkan teks, link, atau nilai variabel di sini..." required><?= isset($global) ? esc($global['key_value']) : '' ?></textarea>
                <div class="form-text" style="font-size: 0.75rem;">Nilai ini akan menggantikan nama key saat bot mengirim pesan.</div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= base_url('bot-global') ?>" class="btn btn-light border rounded-pill px-4 fw-semibold text-muted">Batal</a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                    <i class="bi bi-save me-1"></i> <?= isset($global) ? 'Update Variabel' : 'Simpan Variabel' ?>
                </button>
            </div>

        </form>
    </div>
</div>
<?= $this->endSection() ?>