<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2">

    <div class="row align-items-md-center justify-content-between mb-3 g-3">

        <!-- Kolom Teks (Akan mengisi sisa ruang di PC, dan full-width di HP) -->
        <div class="col-12 col-md">
            <h4 class="fw-bold text-primary mb-1" style="font-size: 1.25rem;">Jadwal Pengiriman Bot</h4>
            <p class="text-muted small mb-0" style="font-size: 0.8rem;">Timezone: <strong>Asia/Jakarta (WIB)</strong>. Perubahan dibaca bot otomatis.</p>
        </div>

        <div class="col-12 col-md-auto">
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button class="btn btn-primary rounded-pill px-4 py-2 small fw-semibold" onclick="sendDailyReport()">
                    <i class="bi bi-send me-1"></i> Kirim Report
                </button>
                <button class="btn btn-warning rounded-pill px-4 py-2 small fw-semibold" onclick="restartTelegramBot()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Restart Bot
                </button>
            </div>
        </div>

    </div>

    <?php if (session()->getFlashdata('pesan')): ?>
        <div class="alert alert-info py-2 small"><?= esc(session()->getFlashdata('pesan')) ?></div>
    <?php endif; ?>

    <div class="alert alert-light border small py-2 mb-3" style="font-size: 0.75rem;">
        Laporan harian dan evaluasi aktivitas memiliki jadwal terpisah. Menonaktifkan jadwal hanya menghentikan proses tersebut.
    </div>

    <div class="table-responsive border-0 overflow-hidden">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="border-bottom-0 px-1 px-md-3">Proses</th>
                    <th class="border-bottom-0 d-none d-md-table-cell">Jenis</th>
                    <th class="border-bottom-0 px-1 px-md-2" style="width: 100px;">Jam WIB</th>
                    <th class="text-center border-bottom-0 px-1 px-md-2" style="width: 70px;">Status</th>
                    <th class="text-center border-bottom-0 px-1 px-md-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td class="py-2 px-1 px-md-3">
                            <form id="form_schedule_<?= $schedule['id'] ?>" method="post" action="<?= base_url('bot-schedules/update/' . (int) $schedule['id']) ?>">
                                <?= csrf_field() ?>
                            </form>

                            <span class="fw-semibold text-dark d-block" style="font-size: 0.85rem; line-height: 1.2;">
                                <?= esc($schedule['schedule_name']) ?>
                            </span>
                            <span class="badge bg-secondary d-md-none mt-1" style="font-size: 0.65rem;">
                                <?= esc($schedule['schedule_type']) ?>
                            </span>
                        </td>

                        <td class="d-none d-md-table-cell">
                            <span class="badge bg-secondary"><?= esc($schedule['schedule_type']) ?></span>
                        </td>

                        <td class="px-1 px-md-2">
                            <input form="form_schedule_<?= $schedule['id'] ?>" class="form-control form-control-sm text-center px-1" type="time" name="run_at" value="<?= esc($schedule['run_at']) ?>" required style="min-width: 70px; font-size: 0.85rem;">
                        </td>

                        <td class="text-center px-1 px-md-2">
                            <div class="form-check form-switch d-inline-block m-0" style="min-height: auto;">
                                <input form="form_schedule_<?= $schedule['id'] ?>" class="form-check-input m-0" type="checkbox" name="is_active" value="1" <?= $schedule['is_active'] ? 'checked' : '' ?> style="cursor: pointer; width: 2em; height: 1em;">
                            </div>
                        </td>

                        <td class="text-center px-1 px-md-3">
                            <button form="form_schedule_<?= $schedule['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3" type="submit">
                                <i class="bi bi-save"></i>
                                <span class="d-none d-md-inline ms-1">Simpan</span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    async function restartTelegramBot() {
        const approved = await showAppConfirm('Restart proses PM2 bot_tele_hfm sekarang?', {
            title: 'Restart bot Telegram',
            confirmText: 'Restart sekarang'
        });
        if (!approved) return;
        try {
            const response = await fetch('<?= base_url('bot-schedules/restart-bot') ?>', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                    'Accept': 'application/json'
                }
            });
            const raw = await response.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch (_) {
                data = {
                    error: raw.substring(0, 500)
                };
            }
            if (!response.ok) {
                const detail = data.detail ? `\nDetail: ${data.detail}` : '';
                throw new Error((data.error || `HTTP ${response.status}`) + detail);
            }
            await showAppAlert(data.message || 'bot_tele_hfm berhasil direstart.');
        } catch (error) {
            await showAppAlert('Restart gagal: ' + error.message);
        }
    }

    async function sendDailyReport() {
        const approved = await showAppConfirm('Kirim report harian berdasarkan data yang tersimpan di database sekarang?', {
            title: 'Kirim report manual',
            confirmText: 'Kirim sekarang'
        });
        if (!approved) return;
        try {
            const response = await fetch('<?= base_url('bot-schedules/send-report') ?>', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                    'Accept': 'application/json'
                }
            });
            const raw = await response.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch (_) {
                data = {
                    error: raw.substring(0, 500)
                };
            }
            if (!response.ok) throw new Error(data.error || `HTTP ${response.status}`);
            await showAppAlert(data.message || 'Report harian berhasil dikirim.');
        } catch (error) {
            await showAppAlert('Pengiriman report gagal: ' + error.message);
        }
    }
</script>
<?= $this->endSection() ?>