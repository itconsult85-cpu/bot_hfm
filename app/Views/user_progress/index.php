<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<style>
    .chat-sidebar {
        height: 30vh;
        min-height: 250px;
        overflow-y: auto;
        border-bottom: 1px solid #dee2e6;
    }

    .chat-main {
        height: 55vh;
        min-height: 400px;
        background-color: #efeae2;
        /* Warna soft ala chat web */
    }

    @media (min-width: 768px) {
        .chat-sidebar {
            height: 70vh;
            min-height: 600px;
            border-bottom: none;
            border-right: 1px solid #dee2e6;
        }

        .chat-main {
            height: 70vh;
            min-height: 600px;
        }
    }

    .user-item {
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        border-left: 4px solid transparent;
    }

    .user-item:hover {
        background-color: #f8f9fa;
    }

    .user-item.active {
        background-color: #e9ecef;
        border-left-color: #0d6efd;
    }

    .chat-sidebar::-webkit-scrollbar,
    .chat-history::-webkit-scrollbar {
        width: 6px;
    }

    .chat-sidebar::-webkit-scrollbar-thumb,
    .chat-history::-webkit-scrollbar-thumb {
        background: #ced4da;
        border-radius: 4px;
    }
</style>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4">
    <div class="row align-items-md-center justify-content-between mb-3 g-3">
        <div class="col-12 col-md">
            <h4 class="fw-bold tracking-tight text-primary mb-1" style="font-size: 1.25rem;">
                <i class="bi bi-chat-dots me-2"></i><?= esc($title ?? 'Log Obrolan Telegram') ?>
            </h4>
            <p class="text-muted small mb-0" style="font-size: 0.8rem;">Pantau percakapan bot Telegram dengan pengguna secara real-time.</p>
        </div>
        <div class="col-12 col-md-auto">
            <button type="button" class="btn btn-danger rounded-pill w-100 px-4 py-2 small fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBersihkanLog">
                <i class="bi bi-trash3 me-1"></i> Bersihkan Log
            </button>
        </div>
    </div>

    <?php if (session()->getFlashdata('pesan')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-pill px-4 py-2 mb-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= session()->getFlashdata('pesan') ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border border-light-subtle rounded-4 overflow-hidden shadow-sm">
        <div class="row g-0">

            <div class="col-12 col-md-4 col-lg-3 bg-white chat-sidebar d-flex flex-column">
                <div class="p-3 border-bottom shadow-sm z-1 sticky-top bg-white">
                    <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.9rem;"><i class="bi bi-people-fill me-2 text-primary"></i>Daftar User ID</h6>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchChatLog" class="form-control border-start-0 bg-light px-1" placeholder="Cari ID Telegram..." onkeyup="filterChatList()">
                    </div>
                </div>
                <div id="contactList" class="flex-grow-1 overflow-auto">
                    <?php if (empty($users)): ?>
                        <div class="p-4 text-center text-muted small">Belum ada riwayat percakapan.</div>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <div class="user-item p-3 border-bottom" data-phone="<?= esc($user['phone_number']) ?>" onclick="loadChat(this)">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark text-truncate" style="font-size: 0.85rem;">
                                        <i class="bi bi-person-circle me-1 text-primary"></i> <?= esc($user['phone_number']) ?>
                                    </span>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    <i class="bi bi-clock-history me-1"></i><?= date('d M, H:i', strtotime($user['last_active'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-md-8 col-lg-9 d-flex flex-column p-0">
                <div class="chat-header p-3 bg-white border-bottom shadow-sm z-1 d-flex align-items-center">
                    <div class="bg-primary-subtle text-primary rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 45px; height: 45px;">
                        <i class="bi bi-telegram fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark" id="activeChatName">Pilih kontak...</h6>
                        <small class="text-muted" id="activeChatPhone">Klik ID di sebelah kiri</small>
                    </div>
                </div>

                <div class="chat-history chat-main flex-grow-1 overflow-auto p-3 p-md-4" id="chatHistory">
                    <div class="h-100 d-flex flex-column justify-content-center align-items-center text-muted">
                        <i class="bi bi-chat-square-dots opacity-50 mb-3" style="font-size: 4rem;"></i>
                        <p class="small bg-white px-3 py-1 rounded-pill shadow-sm">Pilih chat untuk melihat riwayat percakapan</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalBersihkanLog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-body p-4 text-center">
                <div class="display-6 text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h5 class="fw-bold mb-2">Bersihkan Semua Log?</h5>
                <p class="text-muted small">Apakah Anda yakin ingin menghapus <strong>SELURUH</strong> riwayat obrolan dari database secara permanen? Tindakan ini tidak dapat dibatalkan.</p>
                <div class="d-grid gap-2 mt-4">
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
        document.getElementById('activeChatPhone').innerText = "Log percakapan aktif";

        chatHistoryBox.innerHTML = '<div class="h-100 d-flex flex-column justify-content-center align-items-center"><div class="spinner-border text-primary" role="status"></div><p class="text-muted mt-3 small">Memuat percakapan...</p></div>';

        fetch(`<?= base_url('chat-logs/getDetailChat/') ?>${phone}`)
            .then(response => response.json())
            .then(data => {
                chatHistoryBox.innerHTML = '';
                if (data.length === 0) {
                    chatHistoryBox.innerHTML = '<div class="text-center mt-5 text-muted small bg-white d-inline-block px-3 py-1 rounded-pill shadow-sm">Data chat kosong.</div>';
                    return;
                }

                data.forEach(chat => {
                    const isUser = (chat.sender !== 'bot' && chat.sender !== 'admin');

                    const wrapper = document.createElement('div');
                    wrapper.className = `d-flex w-100 mb-3 ${isUser ? 'justify-content-end' : 'justify-content-start'}`;

                    const bubble = document.createElement('div');
                    bubble.className = `p-2 px-3 shadow-sm rounded-4 ${isUser ? 'bg-success-subtle text-dark border border-success-subtle rounded-top-end-0' : 'bg-white border border-light-subtle rounded-top-start-0'}`;
                    bubble.style.maxWidth = "85%";

                    let senderLabel = "";
                    if (chat.sender === 'admin') {
                        senderLabel = `<div class="fw-bold text-primary mb-1" style="font-size: 0.7rem;"><i class="bi bi-person-badge"></i> ADMIN</div>`;
                    } else if (chat.sender === 'bot') {
                        senderLabel = `<div class="fw-bold text-info mb-1" style="font-size: 0.7rem;"><i class="bi bi-robot"></i> BOT</div>`;
                    }

                    const timeText = new Date(chat.created_at).toLocaleString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                        day: '2-digit',
                        month: 'short'
                    });

                    bubble.innerHTML = `
                        ${senderLabel}
                        <div style="word-break: break-word; font-size: 0.85rem; line-height: 1.4;">${chat.message}</div>
                        <div class="text-end text-muted mt-1 opacity-75" style="font-size: 0.65rem;">${timeText}</div>
                    `;

                    wrapper.appendChild(bubble);
                    chatHistoryBox.appendChild(wrapper);
                });

                chatHistoryBox.scrollTop = chatHistoryBox.scrollHeight;
            })
            .catch(error => {
                chatHistoryBox.innerHTML = '<div class="text-center mt-5 text-danger small"><i class="bi bi-exclamation-circle me-1"></i> Gagal memuat percakapan.</div>';
            });
    }
</script>
<?= $this->endSection() ?>