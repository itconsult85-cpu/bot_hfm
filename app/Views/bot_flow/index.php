<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<div class="container-fluid mt-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold tracking-tight mb-1">
                <i class="bi bi-diagram-3 text-primary me-2"></i><?= esc($title ?? 'Alur Pendaftaran (Flows)') ?>
            </h3>
            <p class="text-muted small mb-0">Atur langkah-langkah pendaftaran user bot Telegram secara berjenjang.</p>
        </div>
        <a href="<?= base_url('bot-flow/create') ?>" class="btn btn-primary rounded-pill fw-semibold shadow-sm text-nowrap">
            <i class="bi bi-plus-lg me-1"></i> Tambah Alur
        </a>
    </div>

    <?php if (session()->getFlashdata('pesan')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-pill px-4 py-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('pesan') ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="8%" class="text-center py-3 border-bottom-0">Step</th>
                        <th width="15%" class="py-3 border-bottom-0">Nama Step</th>
                        <th width="20%" class="py-3 border-bottom-0">Keywords Trigger</th>
                        <th width="42%" class="py-3 border-bottom-0">Pesan Balasan Utama</th>
                        <th width="15%" class="text-center py-3 border-bottom-0">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($flows)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-diagram-2 text-muted" style="font-size: 3rem;"></i>
                                    <h6 class="fw-bold mt-3">Belum Ada Alur Pendaftaran</h6>
                                    <p class="text-muted small">Tambahkan step pertama untuk memulai.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($flows as $f): ?>
                            <tr>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill px-3 py-2 fs-6 shadow-sm">
                                        <?= esc($f['step_level']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-dark"><?= esc($f['step_name']) ?></td>
                                <td>
                                    <span class="badge bg-light text-secondary border text-wrap text-start lh-base">
                                        <?= esc($f['trigger_keywords']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="d-inline-block text-truncate text-muted small" style="max-width: 350px;">
                                        <?= esc($f['reply_message']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="<?= base_url('bot-flow/edit/' . $f['id']) ?>" class="btn btn-sm btn-light border text-warning rounded-circle" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= base_url('bot-flow/delete/' . $f['id']) ?>" class="btn btn-sm btn-light border text-danger rounded-circle" onclick="return confirm('Yakin ingin menghapus step ini?')" title="Hapus">
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