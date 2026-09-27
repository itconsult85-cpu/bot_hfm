<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-link-45deg text-primary me-2"></i><?= esc($title) ?></h4>
        <p class="text-muted small mb-0">Link dikirim dinamis oleh bot setelah pendaftaran berhasil.</p>
    </div>
    <a href="<?= base_url('bot-group-links/create') ?>" class="btn btn-primary rounded-pill px-4"><i class="bi bi-plus-lg me-1"></i>Tambah Link</a>
</div>
<?php if ($message = session()->getFlashdata('pesan')): ?><div class="alert alert-success alert-dismissible fade show"><?= esc($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($message = session()->getFlashdata('error')): ?><div class="alert alert-danger alert-dismissible fade show"><?= esc($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Platform</th><th>Nama Grup</th><th>Link</th><th>ID Grup</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php if (!$links): ?><tr><td colspan="6" class="text-center text-muted py-5">Belum ada konfigurasi link grup.</td></tr><?php endif; ?>
            <?php foreach ($links as $link): ?>
                <tr>
                    <td><span class="badge <?= $link['platform'] === 'whatsapp' ? 'bg-success' : 'bg-info text-dark' ?>"><i class="bi <?= $link['platform'] === 'whatsapp' ? 'bi-whatsapp' : 'bi-telegram' ?> me-1"></i><?= esc(ucfirst($link['platform'])) ?></span></td>
                    <td class="fw-semibold"><?= esc($link['group_name']) ?></td>
                    <td><a href="<?= esc($link['invite_link']) ?>" target="_blank" rel="noopener noreferrer" class="text-break small"><?= esc($link['invite_link']) ?></a></td>
                    <td><code><?= esc($link['group_id']) ?></code></td>
                    <td><?= $link['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' ?></td>
                    <td class="text-end text-nowrap"><a href="<?= base_url('bot-group-links/edit/' . $link['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-pencil"></i></a> <a href="<?= base_url('bot-group-links/delete/' . $link['id']) ?>" class="btn btn-sm btn-outline-danger rounded-pill" data-confirm="Hapus konfigurasi link ini?"><i class="bi bi-trash"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
