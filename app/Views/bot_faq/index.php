<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<div class="container-fluid mt-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold tracking-tight mb-1">
                <i class="bi bi-question-circle text-primary me-2"></i><?= esc($title ?? 'Tanya Jawab (FAQs)') ?>
            </h3>
            <p class="text-muted small mb-0">Kelola daftar pertanyaan dan balasan otomatis Bot Telegram.</p>
        </div>
        <a href="<?= base_url('bot-faq/create') ?>" class="btn btn-primary rounded-pill px-4 shadow-sm text-nowrap">
            <i class="bi bi-plus-lg me-1"></i> Tambah Data
        </a>
    </div>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show rounded-pill px-4 py-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="border-bottom-0 text-center py-3" style="width: 5%;">No</th>
                        <th class="border-bottom-0 py-3" style="width: 25%;">Keywords</th>
                        <th class="border-bottom-0 py-3" style="width: 40%;">Reply Message</th>
                        <th class="border-bottom-0 py-3" style="width: 15%;">Action Type</th>
                        <th class="border-bottom-0 text-center py-3" style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($faqs)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <h6 class="fw-bold mt-3">Tidak ada data FAQ</h6>
                                    <p class="text-muted small">Belum ada keyword dan balasan otomatis yang dibuat.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1;
                        foreach ($faqs as $faq) : ?>
                            <tr>
                                <td class="text-center text-muted fw-semibold"><?= $i++ ?></td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold" style="white-space: normal; text-align: left;">
                                        <?= esc($faq['keywords']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-muted small" style="white-space: pre-wrap; max-height: 100px; overflow-y: auto;">
                                        <?= esc($faq['reply_message']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= esc($faq['action_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="<?= base_url('bot-faq/edit/' . $faq['id']) ?>" class="btn btn-sm btn-light border text-warning rounded-circle" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= base_url('bot-faq/delete/' . $faq['id']) ?>" class="btn btn-sm btn-light border text-danger rounded-circle" title="Hapus" onclick="return confirm('Yakin ingin menghapus data FAQ ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>