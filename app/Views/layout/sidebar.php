<div class="offcanvas offcanvas-start border-0 shadow" tabindex="-1" id="offcanvasSidebar" style="width: 300px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title text-primary fw-bold fs-5"><i class="bi bi-graph-up-arrow me-2"></i>BOSSCUAN VIP</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <?php
    $uri = uri_string();

    // Logika penentu menu aktif
    $isBotSettings = strpos($uri, 'bot-global') === 0 || strpos($uri, 'bot-flow') === 0 || strpos($uri, 'bot-faq') === 0 || strpos($uri, 'activity-reminder-phases') === 0 || strpos($uri, 'bot-schedules') === 0;
    $isBotData     = strpos($uri, 'user-progress') === 0 || strpos($uri, 'chat-logs') === 0 || strpos($uri, 'activity-reminders') === 0;

    // Logika baru untuk menu Laporan HFM
    $isHfmApi      = strpos($uri, 'AdminDashboard/overallPerformance') === 0 ||
        strpos($uri, 'AdminDashboard/clientPerformance') === 0 ||
        strpos($uri, 'AdminDashboard/clientTrades') === 0 ||
        strpos($uri, 'AdminDashboard/campaigns') === 0 ||
        strpos($uri, 'AdminDashboard/campaignWallets') === 0 ||
        strpos($uri, 'AdminDashboard/campaignTrades') === 0 ||
        strpos($uri, 'AdminDashboard/campaignRawClicks') === 0;
    ?>

    <div class="offcanvas-body p-0 py-3 overflow-y-auto">

        <!-- KELOMPOK 1: MANAJEMEN IB UTAMA -->
        <div class="menu-title">Menu Utama</div>
        <a href="<?= base_url('AdminDashboard') ?>" class="sidebar-menu-link <?= ($uri == 'AdminDashboard' || $uri == '') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard IB
        </a>
        <a href="<?= base_url('AdminDashboard/memberLogs') ?>" class="sidebar-menu-link <?= ($uri == 'AdminDashboard/memberLogs') ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i> Aktivitas Member
        </a>
        <a href="<?= base_url('activity-reminders') ?>" class="sidebar-menu-link <?= (strpos($uri, 'activity-reminders') === 0) ? 'active' : '' ?>">
            <i class="bi bi-person-check-fill"></i> Evaluasi Aktivitas
        </a>
        <a href="<?= base_url('MediaAdmin') ?>" class="sidebar-menu-link <?= (strpos($uri, 'MediaAdmin') === 0) ? 'active' : '' ?>">
            <i class="bi bi-images"></i> Kelola Media
        </a>

        <!-- KELOMPOK 2: LAPORAN API HFM (BARU) -->
        <div class="menu-title mt-2">Laporan Broker (HFM)</div>

        <a class="sidebar-menu-link <?= $isHfmApi ? 'active' : '' ?>" data-bs-toggle="collapse" href="#collapseHfmApi" role="button" aria-expanded="<?= $isHfmApi ? 'true' : 'false' ?>">
            <i class="bi bi-pie-chart-fill"></i> HFM API Reports <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
        </a>
        <div class="collapse <?= $isHfmApi ? 'show' : '' ?>" id="collapseHfmApi">
            <div class="submenu-container">
                <!-- Rangkuman Kinerja -->
                <a href="<?= base_url('AdminDashboard/overallPerformance') ?>" class="submenu-link <?= (strpos($uri, 'AdminDashboard/overallPerformance') === 0) ? 'active' : '' ?>">Overall Performance</a>
                <a href="<?= base_url('AdminDashboard/clientPerformance') ?>" class="submenu-link <?= (strpos($uri, 'AdminDashboard/clientPerformance') === 0) ? 'active' : '' ?>">Client Performance</a>
                <a href="<?= base_url('AdminDashboard/clientTrades') ?>" class="submenu-link <?= (strpos($uri, 'AdminDashboard/clientTrades') === 0) ? 'active' : '' ?>">Client Trades</a>

                <!-- Data Kampanye (Campaigns) -->
                <div class="my-1 border-bottom border-light"></div> <!-- Pemisah tipis -->

                <a href="<?= base_url('AdminDashboard/campaigns') ?>" class="submenu-link <?= (strpos($uri, 'AdminDashboard/campaigns') === 0) ? 'active' : '' ?>">Daftar Kampanye (Campaigns)</a>
                <a href="<?= base_url('AdminDashboard/campaign-wallets') ?>" class="submenu-link <?= (strpos($uri, 'AdminDashboard/campaign-wallets') === 0) ? 'active' : '' ?>">Wallet Registrations</a>
                <a href="<?= base_url('AdminDashboard/campaignTrades') ?>" class="submenu-link <?= (strpos($uri, 'AdminDashboard/campaignTrades') === 0) ? 'active' : '' ?>">Komisi Kampanye (Trades)</a>
                <a href="<?= base_url('AdminDashboard/campaignRawClicks') ?>" class="submenu-link <?= (strpos($uri, 'AdminDashboard/campaignRawClicks') === 0) ? 'active' : '' ?>">Klik Kampanye (Raw Clicks)</a>
            </div>
        </div>

        <!-- KELOMPOK 3: BOT TELEGRAM -->
        <div class="menu-title mt-2">Bot Telegram (Dinamic)</div>

        <a class="sidebar-menu-link <?= $isBotSettings ? 'active' : '' ?>" data-bs-toggle="collapse" href="#collapseBotSettings" role="button" aria-expanded="<?= $isBotSettings ? 'true' : 'false' ?>">
            <i class="bi bi-robot"></i> Pengaturan Bot <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
        </a>
        <div class="collapse <?= $isBotSettings ? 'show' : '' ?>" id="collapseBotSettings">
            <div class="submenu-container">
                <a href="<?= base_url('bot-global') ?>" class="submenu-link <?= (strpos($uri, 'bot-global') === 0) ? 'active' : '' ?>">Variabel Global</a>
                <a href="<?= base_url('bot-flow') ?>" class="submenu-link <?= (strpos($uri, 'bot-flow') === 0) ? 'active' : '' ?>">Alur Pendaftaran (Flows)</a>
                <a href="<?= base_url('bot-faq') ?>" class="submenu-link <?= (strpos($uri, 'bot-faq') === 0) ? 'active' : '' ?>">Tanya Jawab (FAQs)</a>
                <a href="<?= base_url('activity-reminder-phases') ?>" class="submenu-link <?= (strpos($uri, 'activity-reminder-phases') === 0) ? 'active' : '' ?>">Fase Pengingat Aktivitas</a>
                <a href="<?= base_url('bot-schedules') ?>" class="submenu-link <?= (strpos($uri, 'bot-schedules') === 0) ? 'active' : '' ?>">Jadwal Pengiriman &amp; Bot</a>
            </div>
        </div>

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