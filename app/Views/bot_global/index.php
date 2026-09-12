<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<div class="container-fluid mt-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold tracking-tight mb-1">
                <i class="bi bi-globe text-primary me-2"></i><?= esc($title ?? 'Variabel Global Bot') ?>
            </h3>
            <p class="text-muted small mb-0">Kelola kata kunci statis (seperti link atau sapaan) yang digunakan bot di berbagai alur.</p>
        </div>
        <a href="<?= base_url('bot-global/create') ?>" class="btn btn-primary rounded-pill fw-semibold shadow-sm text-nowrap">
            <i class="bi bi-plus-lg me-1"></i> Tambah Variabel
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
                        <th width="5%" class="text-center py-3 border-bottom-0">No</th>
                        <th width="25%" class="py-3 border-bottom-0">Nama Key</th>
                        <th width="55%" class="py-3 border-bottom-0">Nilai (Value)</th>
                        <th width="15%" class="text-center py-3 border-bottom-0">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($globals)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-code-slash text-muted" style="font-size: 3rem;"></i>
                                    <h6 class="fw-bold mt-3">Tidak Ada Variabel</h6>
                                    <p class="text-muted small">Belum ada variabel global yang didaftarkan.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1;
                        foreach ($globals as $g): ?>
                            <tr>
                                <td class="text-center text-muted fw-semibold"><?= $i++ ?></td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold" style="letter-spacing: 0.5px;">
                                        {<?= esc($g['key_name']) ?>}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-muted small pe-3" style="white-space: pre-wrap; max-height: 80px; overflow-y: auto;">
                                        <?= esc($g['key_value']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="<?= base_url('bot-global/edit/' . $g['id']) ?>" class="btn btn-sm btn-light border text-warning rounded-circle" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= base_url('bot-global/delete/' . $g['id']) ?>" class="btn btn-sm btn-light border text-danger rounded-circle" onclick="return confirm('Yakin ingin menghapus variabel ini?')" title="Hapus">
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