<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start align-items-md-center gap-2 mb-3">
        <div class="flex-grow-1">
            <h4 class="mb-1 text-primary fw-bold" style="font-size: 1.25rem;">Evaluasi Aktivitas Member</h4>
            <p class="text-muted small mb-0" style="font-size: 0.8rem;">Member aktif tanpa transaksi sejak bergabung (Usia 7–10 hari).</p>
        </div>
        <span class="badge bg-success rounded-pill px-3 py-2 text-nowrap"><i class="bi bi-cloud-check me-1"></i> API HFM</span>
    </div>

    <?php if (session()->getFlashdata('pesan')): ?>
        <div class="alert alert-info py-2 small"><?= esc(session()->getFlashdata('pesan')) ?></div>
    <?php endif; ?>

    <div class="alert alert-light border p-2 mb-3" style="font-size: 0.75rem;">
        Fase: <strong>H-3</strong> (hari ke-7), <strong>H-1</strong> (hari ke-9), <strong>Evaluasi</strong> (hari ke-10). Tidak ada kick otomatis; admin menekan tombol aksi.
    </div>

    <div class="table-responsive border-0">
        <table class="table table-hover align-middle table-sm mb-0" style="table-layout: auto;">
            <thead class="table-light">
                <tr>
                    <th class="text-center border-bottom-0 px-1 px-md-2" style="width: 35px;">#</th>
                    <th class="border-bottom-0 px-1 px-md-2">Nama</th>
                    <th class="border-bottom-0 d-none d-md-table-cell">ID Trading</th>
                    <th class="border-bottom-0 d-none d-md-table-cell">ID Telegram</th>
                    <th class="border-bottom-0 d-none d-md-table-cell">Bergabung</th>
                    <th class="border-bottom-0 d-none d-md-table-cell">Terakhir Trading</th>
                    <th class="text-center text-md-start border-bottom-0 px-1 px-md-2" style="width: 60px;">Fase</th>

                    <th class="text-center border-bottom-0 px-1 px-md-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Tidak ada member yang jatuh tempo hari ini.</td>
                    </tr>
                    <?php else: $no = 1;
                    foreach ($members as $member): ?>

                        <tr>
                            <td class="text-center px-0 px-md-2">
                                <button class="btn btn-sm btn-light border rounded-circle d-md-none shadow-sm fw-bold p-0 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px;" type="button" data-bs-toggle="collapse" data-bs-target="#expandMember<?= $member['id'] ?>" aria-expanded="false">
                                    <i class="bi bi-chevron-down text-primary" style="font-size: 0.75rem;"></i>
                                </button>
                                <span class="text-muted small fw-semibold d-none d-md-inline"><?= $no++ ?></span>
                            </td>

                            <td class="py-2 px-1 px-md-2" style="min-width: 100px;">
                                <span class="fw-semibold text-dark d-block" style="word-break: break-word; white-space: normal; font-size: 0.85rem; line-height: 1.2;">
                                    <?= esc($member['nama'] ?: '-') ?>
                                </span>
                            </td>

                            <td class="d-none d-md-table-cell px-2">
                                <span class="badge bg-light text-primary border border-primary-subtle px-2 py-1"><?= esc($member['id_hfm']) ?></span>
                            </td>

                            <td class="d-none d-md-table-cell text-nowrap px-2">
                                <div class="text-muted small"><i class="bi bi-telegram text-info me-1"></i><?= esc($member['id_telegram']) ?></div>
                            </td>

                            <td class="d-none d-md-table-cell text-nowrap px-2">
                                <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= esc(date('d M Y', strtotime($member['created_at']))) ?></span>
                            </td>

                            <td class="d-none d-md-table-cell text-nowrap px-2">
                                <span class="text-muted small"><?= esc($member['last_trade_display']) ?></span>
                            </td>

                            <td class="text-center text-md-start px-1 px-md-2">
                                <span class="badge <?= $member['reminder_phase'] === 'final' ? 'bg-danger' : ($member['reminder_phase'] === 'h1' ? 'bg-warning text-dark' : 'bg-info text-dark') ?>" style="font-size: 0.65rem; padding: 0.35rem 0.4rem;">
                                    <?= esc(strtoupper($member['reminder_phase'])) ?>
                                </span>
                            </td>

                            <td class="text-center px-1 px-md-2">
                                Gunakan d-inline-flex, rounded standar untuk mobile, dan rounded-md-pill untuk desktop -->
                                <button type="button" class="btn btn-sm btn-outline-danger rounded rounded-md-pill p-1 px-md-3 py-md-1 btn-kick-member d-inline-flex align-items-center justify-content-center"
                                    data-id="<?= (int) $member['id'] ?>"
                                    data-nama="<?= esc($member['nama'] ?: 'Member Tanpa Nama') ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalKickMember">
                                    <i class="bi bi-person-x m-0" style="font-size: 0.9rem;"></i>
                                    <span class="d-none d-md-inline ms-1">Kick</span>
                                </button>
                            </td>
                        </tr>

                        <tr id="expandMember<?= $member['id'] ?>" class="collapse d-md-none bg-light border-bottom">
                            <td colspan="4" class="p-3 shadow-inner">
                                <ul class="list-unstyled mb-0 small px-1" style="font-size: 0.8rem;">
                                    <li class="mb-2">
                                        <span class="fw-semibold d-block text-muted mb-1">ID Trading</span>
                                        <span class="badge bg-white text-primary border border-primary-subtle px-2 py-1"><?= esc($member['id_hfm']) ?></span>
                                    </li>
                                    <li class="mb-2">
                                        <span class="fw-semibold d-block text-muted mb-1">ID Telegram</span>
                                        <i class="bi bi-telegram text-info me-1"></i><?= esc($member['id_telegram']) ?>
                                    </li>
                                    <li class="mb-2">
                                        <span class="fw-semibold d-block text-muted mb-1">Tanggal Bergabung</span>
                                        <i class="bi bi-calendar3 me-1"></i><?= esc(date('d M Y', strtotime($member['created_at']))) ?>
                                    </li>
                                    <li>
                                        <span class="fw-semibold d-block text-muted mb-1">Terakhir Trading (API HFM)</span>
                                        <?= esc($member['last_trade_display']) ?>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                <?php endforeach;
                endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalKickMember" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow-lg">
            <div class="modal-body p-4 text-center">
                <div class="display-6 text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h5 class="fw-bold mb-2">Kick & Hapus Member?</h5>
                <p class="text-muted small">Apakah Anda yakin ingin mengeluarkan dan menghapus data member <br>
                    <strong class="text-dark" id="teksNamaKick"></strong> secara permanen?
                </p>
                <div class="d-grid gap-2 mt-4">
                    <a href="#" id="linkKickModal" class="btn btn-danger rounded-pill py-2 fw-semibold">
                        <i class="bi bi-person-x me-2"></i>Ya, Kick & Hapus
                    </a>
                    <button type="button" class="btn btn-link text-muted btn-sm text-decoration-none" data-bs-dismiss="modal">Batalkan</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.btn-kick-member').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');

                document.getElementById('teksNamaKick').innerText = nama;

                const linkHapus = document.getElementById('linkKickModal');
                if (linkHapus) {
                    linkHapus.href = "<?= base_url('activity-reminders/remove/') ?>" + id;
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>