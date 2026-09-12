<div class="offcanvas offcanvas-start border-0 shadow" tabindex="-1" id="offcanvasSidebar" style="width: 300px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title text-primary fw-bold fs-5"><i class="bi bi-graph-up-arrow me-2"></i>BOSSCUAN VIP</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <?php
    // Ambil path URI saat ini untuk pengecekan menu aktif
    $uri = uri_string();

    // Logika untuk mengecek apakah submenu Bot Settings atau Bot Data sedang aktif
    $isBotSettings = strpos($uri, 'bot-global') === 0 || strpos($uri, 'bot-flow') === 0 || strpos($uri, 'bot-faq') === 0;
    $isBotData = strpos($uri, 'user-progress') === 0 || strpos($uri, 'chat-logs') === 0;
    ?>

    <div class="offcanvas-body p-0 py-3 overflow-y-auto">

        <div class="menu-title">Menu Utama</div>
        <a href="<?= base_url('AdminDashboard') ?>" class="sidebar-menu-link <?= ($uri == 'AdminDashboard' || $uri == '') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard IB
        </a>
        <a href="<?= base_url('AdminDashboard/campaigns') ?>" class="sidebar-menu-link <?= ($uri == 'AdminDashboard/campaigns') ? 'active' : '' ?>">
            <i class="bi bi-megaphone-fill"></i> Kampanye API
        </a>
        <a href="<?= base_url('AdminDashboard/campaign-wallets') ?>" class="sidebar-menu-link <?= ($uri == 'AdminDashboard/campaign-wallets') ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i> Wallet Registrations
        </a>
        <a href="<?= base_url('AdminDashboard/memberLogs') ?>" class="sidebar-menu-link <?= ($uri == 'AdminDashboard/memberLogs') ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i> Aktivitas Member
        </a>
        <a href="<?= base_url('MediaAdmin') ?>" class="sidebar-menu-link <?= (strpos($uri, 'MediaAdmin') === 0) ? 'active' : '' ?>">
            <i class="bi bi-images"></i> Kelola Media
        </a>

        <div class="menu-title mt-2">Bot Telegram (Dinamic)</div>

        <!-- PENGATURAN BOT -->
        <a class="sidebar-menu-link <?= $isBotSettings ? 'active' : '' ?>" data-bs-toggle="collapse" href="#collapseBotSettings" role="button" aria-expanded="<?= $isBotSettings ? 'true' : 'false' ?>">
            <i class="bi bi-robot"></i> Pengaturan Bot <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
        </a>
        <div class="collapse <?= $isBotSettings ? 'show' : '' ?>" id="collapseBotSettings">
            <div class="submenu-container">
                <a href="<?= base_url('bot-global') ?>" class="submenu-link <?= (strpos($uri, 'bot-global') === 0) ? 'active' : '' ?>">Variabel Global</a>
                <a href="<?= base_url('bot-flow') ?>" class="submenu-link <?= (strpos($uri, 'bot-flow') === 0) ? 'active' : '' ?>">Alur Pendaftaran (Flows)</a>
                <a href="<?= base_url('bot-faq') ?>" class="submenu-link <?= (strpos($uri, 'bot-faq') === 0) ? 'active' : '' ?>">Tanya Jawab (FAQs)</a>
            </div>
        </div>

        <!-- DATA BOT -->
        <a class="sidebar-menu-link <?= $isBotData ? 'active' : '' ?>" data-bs-toggle="collapse" href="#collapseBotData" role="button" aria-expanded="<?= $isBotData ? 'true' : 'false' ?>">
            <i class="bi bi-database-fill"></i> Data Bot <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
        </a>
        <div class="collapse <?= $isBotData ? 'show' : '' ?>" id="collapseBotData">
            <div class="submenu-container">
                <a href="<?= base_url('user-progress') ?>" class="submenu-link <?= (strpos($uri, 'user-progress') === 0) ? 'active' : '' ?>">Progress Pendaftar</a>
                <a href="<?= base_url('chat-logs') ?>" class="submenu-link <?= (strpos($uri, 'chat-logs') === 0) ? 'active' : '' ?>">Log Obrolan Telegram</a>
            </div>
        </div>

    </div>
</div>