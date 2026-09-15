<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4 mx-auto" style="max-width: 700px;">

    <div class="mb-4 text-center text-md-start">
        <h4 class="fw-bold tracking-tight mb-1" style="font-size: 1.25rem;">
            <i class="bi bi-code-square text-primary me-2"></i><?= esc($title ?? 'Form Variabel Global') ?>
        </h4>
        <p class="text-muted small mb-0">Kelola variabel teks dinamis yang akan dipanggil oleh bot Telegram.</p>
    </div>

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
            <textarea name="key_value" class="form-control rounded-3" rows="7"
                placeholder="Masukkan teks, link, atau nilai variabel di sini..." required><?= isset($global) ? esc($global['key_value']) : '' ?></textarea>
            <div class="form-text" style="font-size: 0.75rem;">Nilai ini akan menggantikan nama key secara otomatis saat bot mengirim pesan. Mendukung format multi-baris (Enter).</div>
        </div>

        <div class="d-flex flex-column-reverse flex-md-row justify-content-end gap-2 pt-3 border-top">
            <a href="<?= base_url('bot-global') ?>" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold text-muted w-100" style="max-width: 100%; width: md-auto;">Batal</a>

            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm w-100" style="max-width: 100%; width: md-auto;">
                <i class="bi bi-save me-1"></i> <?= isset($global) ? 'Update Variabel' : 'Simpan Variabel' ?>
            </button>
        </div>

    </form>
</div>
<?= $this->endSection() ?>