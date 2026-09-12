<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<div class="card border-0 shadow-sm rounded-4 p-4 mt-2">
    <h4 class="mb-4 text-primary fw-bold">Data Klien Pasif (>30 Hari)</h4>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Klien</th>
                    <th>Nomor WhatsApp</th>
                    <th>Terakhir Aktif</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                foreach ($pasif as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="fw-semibold"><?= esc($row['nama']) ?></td>
                        <td><?= esc($row['no_wa']) ?></td>
                        <td><span class="badge bg-danger rounded-pill px-3 py-2"><?= esc($row['last_active']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button type="button" class="btn btn-primary mt-3 px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalConfirm">
        Kirim Follow-Up Massal (<?= count($pasif) ?> Klien)
    </button>
</div>

<div class="modal fade" id="modalConfirm" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalConfirmLabel">Konfirmasi Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4">
                Apakah Anda yakin ingin mengirim pesan otomatis via Bot WhatsApp ke <strong><?= count($pasif) ?></strong> klien tersebut?
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success rounded-pill px-4" id="btnEksekusi" onclick="jalankanFollowUp()">Ya, Kirim Sekarang!</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function jalankanFollowUp() {
        const btn = document.getElementById('btnEksekusi');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Memproses...';
        btn.disabled = true;

        fetch('<?= base_url('client/prosesFollowUp') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const modalElement = document.getElementById('modalConfirm');
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    modalInstance.hide();

                    alert(data.dikirim + ' Pesan berhasil terkirim ke antrean WhatsApp!');
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan pada sistem koneksi.');
                btn.innerHTML = 'Ya, Kirim Sekarang!';
                btn.disabled = false;
            });
    }
</script>
<?= $this->endSection() ?>