<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<div class="row align-items-md-center justify-content-between mb-3 g-3">
    <div class="col-12 col-md">
        <h4 class="fw-bold tracking-tight text-primary mb-1" style="font-size: 1.25rem;">
            <i class="bi bi-chat-dots me-2"></i><?= esc($title ?? 'Log Obrolan Telegram') ?>
        </h4>
        <p class="text-muted small mb-0" style="font-size: 0.8rem;">Pantau percakapan bot Telegram dengan pengguna secara real-time.</p>
    </div>
    <div class="col-12 col-md-auto">
        <div class="d-grid d-md-block text-md-end">
            <button type="button" class="btn btn-danger rounded-pill px-4 py-2 small fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBersihkanLog">
                <i class="bi bi-trash3 me-1"></i> Bersihkan Log
            </button>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('pesan')): ?>
    <div class="alert alert-success rounded-pill py-2"><i class="bi bi-check-circle-fill me-2"></i> <?= session()->getFlashdata('pesan') ?></div>
<?php endif; ?>

<div class="chat-container d-flex">
    <div class="user-list">
        <div class="p-3 bg-white border-bottom sticky-top">
            <h6 class="fw-bold mb-2 text-muted"><i class="bi bi-people-fill me-2"></i>Daftar User ID</h6>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="searchChatLog" class="form-control border-start-0 ps-0" placeholder="Cari ID Telegram..." onkeyup="filterChatList()">
            </div>
        </div>
        <div id="contactList">
            <?php if (empty($users)): ?>
                <div class="p-4 text-center text-muted small">Belum ada riwayat percakapan.</div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="user-item p-3" data-phone="<?= esc($user['phone_number']) ?>" onclick="loadChat(this)">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark text-truncate"><i class="bi bi-person-fill me-1 text-primary"></i> <?= esc($user['phone_number']) ?></span>
                        </div>
                        <div class="small text-muted"><i class="bi bi-clock-history me-1"></i><?= date('d/m H:i', strtotime($user['last_active'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="chat-area">
        <div class="chat-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 40px; height: 40px;">
                    <i class="bi bi-telegram fs-5"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold" id="activeChatName">Pilih kontak...</h6>
                    <small class="text-muted" id="activeChatPhone">Klik ID di sebelah kiri</small>
                </div>
            </div>
        </div>

        <div class="chat-history" id="chatHistory">
            <div class="h-100 d-flex flex-column justify-content-center align-items-center text-muted">
                <i class="bi bi-chat-square-text fs-1 mb-2"></i>
                <p>Pilih chat untuk melihat riwayat percakapan</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Bersihkan Log (Seragam) -->
<div class="modal fade" id="modalBersihkanLog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-body p-4 text-center">
                <div class="display-6 text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h5 class="fw-bold mb-2">Bersihkan Semua Log?</h5>
                <p class="text-muted small">Apakah Anda yakin ingin menghapus <strong>SELURUH</strong> riwayat obrolan dari database secara permanen? Tindakan ini tidak dapat dibatalkan.</p>
                <div class="d-grid gap-2 mt-4">
                    <!-- Link ini yang akan mengeksekusi penghapusan -->
                    <a href="<?= base_url('chat-logs/clear-all') ?>" class="btn btn-danger rounded-pill py-2 fw-semibold">
                        <i class="bi bi-trash3 me-2"></i>Ya, Hapus Semua
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
    function filterChatList() {
        let input = document.getElementById("searchChatLog").value.toLowerCase();
        let items = document.querySelectorAll(".user-item");

        items.forEach(item => {
            let idText = item.getAttribute("data-phone").toLowerCase();

            if (idText.includes(input)) {
                item.style.display = "block";
            } else {
                item.style.display = "none";
            }
        });
    }

    function loadChat(element) {
        document.querySelectorAll('.user-item').forEach(el => el.classList.remove('active'));
        element.classList.add('active');

        const phone = element.getAttribute('data-phone');
        const chatHistoryBox = document.getElementById('chatHistory');

        document.getElementById('activeChatName').innerText = "ID Telegram: " + phone;
        document.getElementById('activeChatPhone').innerText = "Telegram Logs";

        chatHistoryBox.innerHTML = '<div class="text-center mt-5"><div class="spinner-border text-primary" role="status"></div><p class="text-muted mt-2">Memuat percakapan...</p></div>';

        fetch(`<?= base_url('chat-logs/getDetailChat/') ?>${phone}`)
            .then(response => response.json())
            .then(data => {
                chatHistoryBox.innerHTML = '';
                if (data.length === 0) {
                    chatHistoryBox.innerHTML = '<div class="text-center mt-5 text-muted">Data chat kosong.</div>';
                    return;
                }

                data.forEach(chat => {
                    const bubble = document.createElement('div');
                    let senderLabel = "";

                    if (chat.sender === 'bot') {
                        bubble.className = 'bubble bubble-bot';
                    } else if (chat.sender === 'admin') {
                        bubble.className = 'bubble bubble-admin';
                        senderLabel = `<div class="fw-bold text-primary mb-1" style="font-size: 0.75rem;"><i class="bi bi-person-badge"></i> ADMIN</div>`;
                    } else {
                        bubble.className = 'bubble bubble-user';
                    }

                    const timeText = new Date(chat.created_at).toLocaleString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                        day: '2-digit',
                        month: 'short'
                    });
                    bubble.innerHTML = `${senderLabel}${chat.message} <div class="text-end text-muted mt-2" style="font-size: 0.65rem;">${timeText}</div>`;
                    chatHistoryBox.appendChild(bubble);
                });
                chatHistoryBox.scrollTop = chatHistoryBox.scrollHeight;
            })
            .catch(error => {
                chatHistoryBox.innerHTML = '<div class="text-center mt-5 text-danger">Gagal memuat percakapan.</div>';
            });
    }
</script>
<?= $this->endSection() ?>