<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="card border-0 shadow-sm rounded-4 p-4 mt-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="text-primary fw-bold mb-1">Pengaturan Fase Pengingat</h4><p class="text-muted mb-0">Atur jumlah fase, hari setelah bergabung, dan isi pesan Telegram.</p></div>
        <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#phaseModal" onclick="newPhase()"><i class="bi bi-plus-lg"></i> Tambah Fase</button>
    </div>
    <?php if ($msg = session()->getFlashdata('pesan')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <div class="alert alert-light border small">Fase aktif akan diproses tepat pada jumlah hari setelah <code>created_at</code>. Anda bebas menghapus fase, menambah fase baru, atau mengubah urutannya berdasarkan hari.</div>
    <div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>No</th><th>Key</th><th>Nama</th><th>Hari ke-</th><th>Status</th><th>Pesan</th><th>Aksi</th></tr></thead><tbody>
    <?php $no = 1; foreach ($phases as $phase): ?><tr>
        <td><?= $no++ ?></td><td><code><?= esc($phase['phase_key']) ?></code></td><td><?= esc($phase['phase_name']) ?></td><td><?= esc($phase['days_after_join']) ?></td>
        <td><?= $phase['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' ?></td>
        <td><div style="white-space:pre-wrap;max-width:520px;max-height:100px;overflow:auto"><?= esc($phase['message']) ?></div></td>
        <td><div class="d-flex gap-1"><button class="btn btn-sm btn-outline-warning" onclick='editPhase(<?= json_encode($phase, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'><i class="bi bi-pencil"></i></button><a class="btn btn-sm btn-outline-danger" href="<?= base_url('activity-reminder-phases/delete/' . $phase['id']) ?>" data-confirm="Hapus fase ini?" data-confirm-title="Hapus fase reminder" data-confirm-button="Ya, hapus"><i class="bi bi-trash"></i></a></div></td>
    </tr><?php endforeach; ?>
    </tbody></table></div>
</div>
<div class="modal fade" id="phaseModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="phaseForm" method="post"><div class="modal-header"><h5 class="modal-title">Fase Pengingat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
    <div class="row g-3"><div class="col-md-4"><label class="form-label">Key</label><input class="form-control" name="phase_key" id="phase_key" required></div><div class="col-md-5"><label class="form-label">Nama Fase</label><input class="form-control" name="phase_name" id="phase_name" required></div><div class="col-md-3"><label class="form-label">Hari Setelah Bergabung</label><input class="form-control" type="number" min="0" name="days_after_join" id="days_after_join" required></div><div class="col-12"><label class="form-label">Pesan Telegram</label><textarea class="form-control" name="message" id="message" rows="10" required></textarea></div><div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked><label class="form-check-label" for="is_active">Fase aktif</label></div></div>
</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan</button></div></form></div></div></div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?><script>
const basePhase='<?= base_url('activity-reminder-phases') ?>';
function newPhase(){document.getElementById('phaseForm').action=basePhase+'/store';document.getElementById('phaseForm').reset();document.getElementById('is_active').checked=true;}
function editPhase(p){document.getElementById('phaseForm').action=basePhase+'/update/'+p.id;for(const k of ['phase_key','phase_name','days_after_join','message']) document.getElementById(k).value=p[k];document.getElementById('is_active').checked=Number(p.is_active)===1;new bootstrap.Modal(document.getElementById('phaseModal')).show();}
</script><?= $this->endSection() ?>
