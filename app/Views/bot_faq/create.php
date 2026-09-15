<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4 mx-auto" style="max-width: 700px;">

    <div class="mb-4 text-center text-md-start">
        <h4 class="fw-bold tracking-tight mb-1" style="font-size: 1.25rem;">
            <i class="bi <?= isset($faq) ? 'bi-pencil-square' : 'bi-plus-circle' ?> text-primary me-2"></i><?= esc($title ?? (isset($faq) ? 'Edit Data FAQ' : 'Tambah Data FAQ')) ?>
        </h4>
        <p class="text-muted small mb-0"><?= isset($faq) ? 'Perbarui data keyword atau teks balasan bot Anda.' : 'Tambahkan keyword baru dan balasan otomatis untuk bot Anda.' ?></p>
    </div>

    <form action="<?= isset($faq) ? base_url('bot-faq/update/' . $faq['id']) : base_url('bot-faq/store') ?>" method="POST">

        <div class="mb-3">
            <label class="form-label fw-semibold text-muted small">Keywords (Pisahkan dengan koma)</label>
            <input type="text" name="keywords" class="form-control rounded-3"
                value="<?= isset($faq) ? esc($faq['keywords']) : '' ?>"
                placeholder="Contoh: cara daftar, daftar, registrasi" required>
            <div class="form-text" style="font-size: 0.75rem;">Kata kunci yang memicu balasan ini jika dikirimkan oleh user.</div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold text-muted small">Reply Message</label>
            <textarea name="reply_message" class="form-control rounded-3" rows="6"
                placeholder="Masukkan balasan teks bot di sini..." required><?= isset($faq) ? esc($faq['reply_message']) : '' ?></textarea>
            <div class="form-text" style="font-size: 0.75rem;">Mendukung format multi-baris (Enter).</div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold text-muted small">Action Type</label>
            <select name="action_type" class="form-select rounded-3">
                <option value="reply_only" <?= (isset($faq) && $faq['action_type'] == 'reply_only') ? 'selected' : '' ?>>Reply Only</option>
                <option value="send_video_pindah" <?= (isset($faq) && $faq['action_type'] == 'send_video_pindah') ? 'selected' : '' ?>>Send Video Pindah IB</option>
            </select>
            <div class="form-text" style="font-size: 0.75rem;">Tentukan apakah bot hanya membalas pesan, atau memicu tindakan spesifik lainnya.</div>
        </div>

        <div class="d-flex flex-column-reverse flex-md-row justify-content-end gap-2 pt-3 border-top">
            <a href="<?= base_url('bot-faq') ?>" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold text-muted w-100" style="max-width: 100%; width: md-auto;">Batal</a>

            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm w-100" style="max-width: 100%; width: md-auto;">
                <i class="bi <?= isset($faq) ? 'bi-check-circle' : 'bi-save' ?> me-1"></i> <?= isset($faq) ? 'Update Data' : 'Simpan Data' ?>
            </button>
        </div>

    </form>
</div>
<?= $this->endSection() ?>