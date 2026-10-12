<?php

namespace App\Controllers;

use App\Libraries\ActivityReminderService;
use CodeIgniter\Controller;

class AdminDashboard extends BaseController
{
    private $apiKey = '';

    public function __construct()
    {
        $this->apiKey = (string) (getenv('HFM_API_KEY') ?: '');
    }
    private $baseUrl = "https://api.hfm-partners.com/api";

    private function postBotJson(string $path, array $payload): array
    {
        $db = \Config\Database::connect();
        $tokenRow = $db->table('bot_globals')->select('key_value')
            ->where('key_name', 'BOT_CONTROL_TOKEN')->orderBy('id', 'DESC')->get()->getRowArray();
        $token = trim((string) ($tokenRow['key_value'] ?? ''));
        if ($token === '') {
            return ['ok' => false, 'message' => 'BOT_CONTROL_TOKEN belum dikonfigurasi.'];
        }

        $ch = curl_init('http://127.0.0.1:3000' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Bot-Control-Token: ' . $token],
            CURLOPT_POSTFIELDS => json_encode($payload + ['token' => $token]),
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
        ]);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($raw ?: '', true);
        return [
            'ok' => !$error && $httpCode >= 200 && $httpCode < 300 && (($data['status'] ?? null) === 'success' || $httpCode === 200),
            'message' => $error ?: ($data['message'] ?? $data['error'] ?? ('HTTP ' . $httpCode)),
        ];
    }

    // ==========================================
    // DASHBOARD UTAMA
    // ==========================================
    public function index()
    {
        $db = \Config\Database::connect();

        $hariIni = date('Y-m-d');
        $kemarin = date('Y-m-d', strtotime('-1 day'));

        $data['aktif_total'] = $db->table('tb_member_vip')->where('status', 'aktif')->countAllResults();
        $data['baru_hari_ini'] = $db->table('tb_member_vip')->like('created_at', $hariIni)->countAllResults();
        $data['baru_kemarin']  = $db->table('tb_member_vip')->like('created_at', $kemarin)->countAllResults();

        $hasLogTelegram = $this->hasMemberLogTelegramColumn($db);
        $lepasIdentity = $hasLogTelegram
            ? "COALESCE(NULLIF(NULLIF(id_telegram, ''), '-'), no_wa)"
            : 'no_wa';
        $data['lepas_hari_ini'] = (int) ($db->table('tb_member_logs')
            ->select("COUNT(DISTINCT {$lepasIdentity}) AS total", false)
            ->where('tipe_aktivitas', 'lepas_ib')
            ->where('created_at >=', $hariIni . ' 00:00:00')
            ->where('created_at <=', $hariIni . ' 23:59:59')
            ->get()->getRowArray()['total'] ?? 0);
        $data['lepas_kemarin'] = (int) ($db->table('tb_member_logs')
            ->select("COUNT(DISTINCT {$lepasIdentity}) AS total", false)
            ->where('tipe_aktivitas', 'lepas_ib')
            ->where('created_at >=', $kemarin . ' 00:00:00')
            ->where('created_at <=', $kemarin . ' 23:59:59')
            ->get()->getRowArray()['total'] ?? 0);

        // Status pasif harus memakai last_trade terbaru dari API HFM, bukan nilai cache lokal.
        $data['pasif_total'] = count((new ActivityReminderService())->inactiveMembersFromApi(30));

        $data['members'] = $db->table('tb_member_vip')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('admin_dashboard', $data);
    }

    // ==========================================
    // BACKUP DATABASE MYSQL
    // ==========================================
    public function backupDatabase()
    {
        // 1. Ambil Kredensial Database Otomatis dari Config CI4
        $db = \Config\Database::connect();
        $hostname = $db->hostname;
        $username = $db->username;
        $password = $db->password;
        $database = $db->database;

        // 2. Siapkan Nama File & Lokasi Penyimpanan Sementara di VPS
        $fileName = 'Backup_BOSSCUAN_' . date('d-M-Y_H-i') . '.sql';
        $savePath = WRITEPATH . 'uploads/' . $fileName;

        // 3. Bangun Perintah mysqldump untuk Server Linux (VPS)
        $cmd = sprintf(
            'mysqldump -h %s -u %s %s %s > %s',
            escapeshellarg($hostname),
            escapeshellarg($username),
            $password ? '-p' . escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($savePath)
        );

        // Eksekusi Perintah di VPS
        exec($cmd, $output, $returnVar);

        // 4. Jika Berhasil, Download File lalu Hapus dari Server agar SSD tidak penuh
        if ($returnVar === 0 && file_exists($savePath)) {
            $fileContent = file_get_contents($savePath);
            unlink($savePath); // Hapus file dari VPS

            return $this->response->download($fileName, $fileContent);
        } else {
            // Jika VPS menolak fungsi exec()
            session()->setFlashdata('pesan', 'Gagal membackup database! Pastikan fungsi exec() diizinkan di VPS.');
            return redirect()->to(base_url('AdminDashboard'));
        }
    }

    // ==========================================
    // HALAMAN MEMBER LOGS (ANTI DUPLIKAT & BALANCE)
    // ==========================================
    public function memberLogs()
    {
        $db = \Config\Database::connect();

        $hasLogTelegram = $this->hasMemberLogTelegramColumn($db);

        // Ambil parameter filter
        $search = $this->request->getGet('search') ?? '';
        $tipe = $this->request->getGet('tipe') ?? '';
        $status = $this->request->getGet('status') ?? ''; // Filter Status Terakhir dari Dashboard Utama
        $date_from = $this->request->getGet('date_from') ?? '';
        $date_to = $this->request->getGet('date_to') ?? '';
        $pageRaw = trim((string) ($this->request->getGet('page') ?? '1'));
        $page = ctype_digit($pageRaw) ? max(1, (int) $pageRaw) : 1;
        $perPage = 20;

        // Build query
        $builder = $db->table('tb_member_logs');
        $logTelegramSelect = $hasLogTelegram
            ? "COALESCE(NULLIF(NULLIF(tb_member_logs.id_telegram, ''), '-'), NULLIF(NULLIF(tb_member_vip.id_telegram, ''), '-'))"
            : 'tb_member_vip.id_telegram';
        $statusFromLog = "CASE WHEN tb_member_logs.tipe_aktivitas IN ('lepas_ib', 'kick_massal_lepas_ib') THEN 'lepas_ib' ELSE NULL END";
        $builder->select("tb_member_logs.*, COALESCE(tb_member_vip.status, {$statusFromLog}) as status_member, {$logTelegramSelect} as id_telegram");

        // Setiap baris log adalah satu kejadian; join berdasarkan ID HFM agar
        // satu kejadian tidak berlipat saat satu nomor WA memiliki beberapa akun.
        $builder->join('tb_member_vip', 'tb_member_vip.id_hfm = tb_member_logs.id_hfm', 'left');
        $builder->orderBy('tb_member_logs.created_at', 'DESC');
        $builder->orderBy('tb_member_logs.id', 'DESC');

        // Filter search
        if (!empty($search)) {
            $builder->groupStart()
                ->like('tb_member_logs.no_wa', $search)
                ->orLike('tb_member_logs.nama', $search)
                ->orLike('tb_member_logs.tipe_aktivitas', $search)
                ->orLike('tb_member_logs.id_hfm', $search)
                ->groupEnd();
        }

        // Filter berdasarkan Tipe Aktivitas Log (Masuk, Keluar, dll)
        if (!empty($tipe)) {
            $builder->where('tb_member_logs.tipe_aktivitas', $tipe);
        }

        // FILTER UTAMA: Menyaring log berdasarkan status member saat ini di Dashboard Utama (tb_member_vip)
        if (!empty($status)) {
            if ($status === 'lepas_ib') {
                $builder->groupStart()
                    ->where('tb_member_vip.status', 'lepas_ib')
                    ->orGroupStart()
                    ->where('tb_member_vip.id IS NULL', null, false)
                    ->where('tb_member_logs.tipe_aktivitas', 'lepas_ib')
                    ->groupEnd()
                    ->groupEnd();
            } else {
                $builder->where('tb_member_vip.status', $status);
            }
        }

        // Filter tanggal
        if (!empty($date_from)) {
            $builder->where('tb_member_logs.created_at >=', $date_from . ' 00:00:00');
        }
        if (!empty($date_to)) {
            $builder->where('tb_member_logs.created_at <=', $date_to . ' 23:59:59');
        }

        // Hitung total data untuk pagination (menahan query agar count akurat)
        $totalData = $builder->countAllResults(false);
        $totalPages = (int) ceil($totalData / $perPage);
        $page = $totalPages > 0 ? min($page, $totalPages) : 1;
        $offset = ($page - 1) * $perPage;

        // Ambil data dengan pagination
        $data['logs'] = $builder->get($perPage, $offset)->getResultArray();
        $data['pagerLinks'] = service('pager')->makeLinks($page, $perPage, $totalData, 'default_full');

        // Data untuk view
        $data['search'] = $search;
        $data['tipe'] = $tipe;
        $data['status'] = $status; // Kirim status ke view agar dropdown tidak reset
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;
        $data['currentPage'] = $page;
        $data['totalPages'] = $totalPages;
        $data['totalData'] = $totalData;
        $data['perPage'] = $perPage;

        // Ambil daftar tipe aktivitas unik untuk dropdown filter
        $data['tipe_aktivitas_list'] = $db->table('tb_member_logs')
            ->select('tipe_aktivitas')
            ->distinct()
            ->get()
            ->getResultArray();

        // Rekap kartu memakai ID trading (id_hfm) sebagai identitas bisnis.
        // DISTINCT mencegah satu akun dihitung berulang meskipun memiliki banyak event.
        // Query ini sengaja tidak melakukan JOIN agar satu log tidak berlipat ketika
        // data roster berubah. Semua baris tb_member_logs tetap ditampilkan lengkap.
        $qTotal = $this->countUniqueLoggedTradingAccounts($db);
        $qMasuk = $this->countUniqueLoggedTradingAccounts($db, ['masuk', 'masuk_id_baru', 'aktif_kembali', 'masuk_via_form']);
        $qKeluar = $this->countUniqueLoggedTradingAccounts($db, ['keluar_sendiri', 'keluar_di_remove', 'dihapus_admin_web', 'lepas_ib', 'keluar_grup']);
        $qHariIni = $this->countUniqueLoggedTradingAccounts($db, null, date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'));
        $data['stats'] = [
            'total'    => $qTotal,
            'masuk'    => $qMasuk,
            'keluar'   => $qKeluar,
            'hari_ini' => $qHariIni,
        ];

        $telegramGroup = $db->table('bot_group_links')
            ->select('group_id, group_name')
            ->where('platform', 'telegram')
            ->where('is_active', 1)
            ->get()->getRowArray();
        $groupId = trim((string) ($telegramGroup['group_id'] ?? ''));
        $kickCandidates = $this->countLePasIbKickCandidates($db, $hasLogTelegram, $groupId);
        $data['lepasIbTelegramCount'] = $kickCandidates;
        $data['kickTargetGroupName'] = (string) ($telegramGroup['group_name'] ?? '');
        $data['kickTargetGroupId'] = $groupId;

        return view('admin_member_logs', $data);
    }

    public function adminHelpSessions()
    {
        $db = \Config\Database::connect();
        $requests = [];
        $errorMessage = '';

        try {
            if (!$db->tableExists('chat_logs_tele')) {
                throw new \RuntimeException('Tabel chat_logs_tele belum tersedia.');
            }

            $builder = $db->table('chat_logs_tele AS help_log')
                ->select('help_log.phone_number AS userId, MAX(help_log.created_at) AS requestedAt', false)
                ->where('help_log.sender', 'bot')
                ->groupStart()
                    ->like('help_log.message', 'Riwayat chat kakak sudah dikirim satu kali ke admin', 'after')
                    ->orLike('help_log.message', 'Riwayat chat kakak sudah diteruskan ke admin', 'after')
                ->groupEnd()
                ->where('help_log.phone_number IS NOT NULL', null, false)
                ->where('help_log.phone_number !=', '')
                ->groupBy('help_log.phone_number')
                ->orderBy('requestedAt', 'DESC')
                ->limit(100);

            if ($db->tableExists('user_progress')
                && $db->fieldExists('phone_number', 'user_progress')
                && $db->fieldExists('user_name', 'user_progress')
                && $db->fieldExists('current_step', 'user_progress')) {
                $builder->select('MAX(progress.user_name) AS userName, MAX(progress.current_step) AS currentStep', false)
                    ->join('user_progress AS progress', 'progress.phone_number = help_log.phone_number', 'left');
            } else {
                $builder->select('help_log.phone_number AS userName, 0 AS currentStep', false);
            }

            $requests = $builder->get()->getResultArray();
        } catch (\Throwable $error) {
            log_message('error', 'Gagal memuat permintaan bantuan dari database: ' . $error->getMessage());
            $errorMessage = 'Daftar permintaan bantuan tidak dapat dibaca dari database. Periksa koneksi MySQL dan tabel chat_logs_tele.';
        }

        return view('admin_help_sessions', [
            'title' => 'Permintaan Bantuan Admin',
            'requests' => $requests,
            'errorMessage' => $errorMessage,
            'refreshedAt' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Unduh user Lepas IB yang dikick admin secara manual maupun massal. */
    public function exportKickedMembers()
    {
        $db = \Config\Database::connect();
        $hasLogTelegram = $this->hasMemberLogTelegramColumn($db);
        $telegramSelect = $hasLogTelegram ? 'kick.id_telegram' : 'NULL AS id_telegram';
        $sql = "SELECT kick.nama, kick.no_wa, {$telegramSelect}, kick.id_hfm, kick.tipe_aktivitas, kick.created_at
            FROM tb_member_logs AS kick
            WHERE kick.tipe_aktivitas = ?
               OR (
                    kick.tipe_aktivitas IN ('keluar_di_remove', 'dihapus_admin_web')
                    AND kick.id_hfm IS NOT NULL
                    AND EXISTS (
                        SELECT 1 FROM tb_member_logs AS lepas
                        WHERE lepas.id_hfm = kick.id_hfm
                          AND lepas.tipe_aktivitas = 'lepas_ib'
                          AND lepas.id = (
                              SELECT MAX(previous_lepas.id) FROM tb_member_logs AS previous_lepas
                              WHERE previous_lepas.id_hfm = kick.id_hfm
                                AND previous_lepas.tipe_aktivitas = 'lepas_ib'
                                AND previous_lepas.id < kick.id
                          )
                          AND NOT EXISTS (
                              SELECT 1 FROM tb_member_logs AS status_change
                              WHERE status_change.id_hfm = kick.id_hfm
                                AND status_change.id > lepas.id
                                AND status_change.id < kick.id
                                AND status_change.tipe_aktivitas IN ('aktif_kembali', 'masuk', 'masuk_id_baru', 'masuk_via_form')
                          )
                    )
               )
            ORDER BY kick.created_at DESC, kick.id DESC";
        $logs = $db->query($sql, ['kick_massal_lepas_ib'])->getResultArray();

        if ($logs === []) {
            return redirect()->to(base_url('AdminDashboard/memberLogs'))
                ->with('warning', 'Belum ada user Lepas IB yang tercatat dikick admin, baik manual maupun massal.');
        }

        $rows = [];
        foreach ($logs as $index => $log) {
            $rows[] = [
                $index + 1,
                $log['nama'] ?? '',
                $log['no_wa'] ?? '',
                $log['id_telegram'] ?? '',
                $log['id_hfm'] ?? '',
                $log['tipe_aktivitas'] === 'kick_massal_lepas_ib'
                    ? 'Kick massal Lepas IB'
                    : 'Kick admin (status Lepas IB)',
                $log['created_at'] ?? '',
            ];
        }

        try {
            $xlsx = (new \App\Libraries\SimpleXlsxExporter())->create([
                'No',
                'Nama',
                'Nomor WhatsApp',
                'ID Telegram',
                'ID Trading',
                'Aktivitas',
                'Waktu Kick',
            ], $rows);
        } catch (\Throwable $exception) {
            log_message('error', 'Gagal membuat ekspor Excel user terkick: ' . $exception->getMessage());
            return redirect()->to(base_url('AdminDashboard/memberLogs'))
                ->with('error', 'File Excel gagal dibuat. Pastikan ekstensi ZIP PHP tersedia.');
        }

        $fileName = 'data_user_terkick_' . date('Ymd_His') . '.xlsx';
        return $this->response->download($fileName, $xlsx)
            ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '');
    }

    public function kickLepasIbMembers()
    {
        set_time_limit(0);
        $db = \Config\Database::connect();
        $tokenRow = $db->table('bot_globals')->select('key_value')
            ->where('key_name', 'BOT_CONTROL_TOKEN')->orderBy('id', 'DESC')->get()->getRowArray();
        $token = trim((string) ($tokenRow['key_value'] ?? ''));
        if ($token === '') {
            return redirect()->to(base_url('AdminDashboard/memberLogs'))
                ->with('error', 'BOT_CONTROL_TOKEN belum dikonfigurasi.');
        }

        $botHost = trim((string) (getenv('BOT_API_HOST') ?: '127.0.0.1'));
        $botPort = (int) (getenv('API_PORT') ?: 3000);
        if ($botPort < 1 || $botPort > 65535) {
            $botPort = 3000;
        }
        $botUrl = sprintf('http://%s:%d/api/kick-lepas-ib-members', $botHost, $botPort);

        $capabilityUrl = sprintf('http://%s:%d/api/capabilities', $botHost, $botPort);
        $capabilityCurl = curl_init($capabilityUrl);
        curl_setopt_array($capabilityCurl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['X-Bot-Control-Token: ' . $token],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
        ]);
        $capabilityRaw = curl_exec($capabilityCurl);
        $capabilityError = curl_error($capabilityCurl);
        $capabilityCode = (int) curl_getinfo($capabilityCurl, CURLINFO_HTTP_CODE);
        curl_close($capabilityCurl);
        $capabilities = json_decode($capabilityRaw ?: '', true);
        if ($capabilityError || $capabilityCode < 200 || $capabilityCode >= 300 ||
            ($capabilities['capabilities']['bulk_kick_ids'] ?? false) !== true) {
            $capabilityMessage = $capabilityCode === 401 && !$capabilityError
                ? 'Token CI4 dan bot tidak cocok; kick massal tidak dijalankan.'
                : 'Versi bot di VPS belum mendukung pencatatan ID hasil kick. Perbarui bot_old.js dan restart PM2; kick massal belum dijalankan.';
            if ($capabilityError) $capabilityMessage .= ' (' . $capabilityError . ')';
            return redirect()->to(base_url('AdminDashboard/memberLogs'))->with('error', $capabilityMessage);
        }

        $ch = curl_init($botUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Bot-Control-Token: ' . $token],
            CURLOPT_POSTFIELDS => json_encode(['token' => $token]),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 600,
        ]);
        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $result = json_decode($raw ?: '', true);

        if ($curlError || $httpCode < 200 || $httpCode >= 300 || ($result['status'] ?? '') !== 'success') {
            $message = $httpCode === 401 && !$curlError
                ? 'Bot menolak BOT_CONTROL_TOKEN. Samakan konfigurasi database CI4 dan bot, hapus spasi/newline di awal atau akhir token, lalu restart proses bot.'
                : ($curlError ?: (string) ($result['error'] ?? 'Bot gagal menjalankan kick massal (HTTP ' . $httpCode . ').'));
            if ($curlError) {
                $message = 'Bot tidak dapat dihubungi pada ' . $botUrl . ': ' . $message;
            }
            return redirect()->to(base_url('AdminDashboard/memberLogs'))->with('error', $message);
        }

        $reportedKickedCount = max(0, (int) ($result['kicked'] ?? 0));
        $kickedTelegramIds = [];
        if (is_array($result['kicked_ids'] ?? null)) {
            foreach ($result['kicked_ids'] as $telegramId) {
                $telegramId = trim((string) $telegramId);
                if ($telegramId !== '' && ctype_digit($telegramId)) {
                    $kickedTelegramIds[$telegramId] = $telegramId;
                }
            }
            $kickedTelegramIds = array_values($kickedTelegramIds);
        }
        if ($reportedKickedCount > 0 && count($kickedTelegramIds) !== $reportedKickedCount) {
            return redirect()->to(base_url('AdminDashboard/memberLogs'))->with(
                'error',
                'Bot melaporkan kick berhasil tetapi daftar ID hasilnya tidak lengkap. Data roster tidak dibersihkan; perbarui bot_old.js di VPS agar hasil kick dapat dicatat dan diunduh dengan akurat.'
            );
        }

        // Tombol Kick adalah satu-satunya aksi yang membersihkan roster Lepas IB.
        // tb_member_logs tidak dihapus sehingga riwayat aktivitas tetap tersedia.
        $db->transBegin();
        if ($this->hasMemberLogTelegramColumn($db)) {
            $db->query(
                "UPDATE tb_member_logs AS log_entry
                 INNER JOIN tb_member_vip AS member ON member.id_hfm = log_entry.id_hfm
                 SET log_entry.id_telegram = member.id_telegram
                 WHERE member.status = 'lepas_ib'
                   AND member.id_telegram IS NOT NULL
                   AND TRIM(member.id_telegram) NOT IN ('', '-')
                   AND (log_entry.id_telegram IS NULL OR TRIM(log_entry.id_telegram) IN ('', '-'))"
            );
        }
        $hasLogTelegram = $this->hasMemberLogTelegramColumn($db);
        foreach ($kickedTelegramIds as $telegramId) {
            $memberRows = $db->query(
                "SELECT no_wa, nama, id_hfm FROM tb_member_vip WHERE status = 'lepas_ib' AND TRIM(id_telegram) = ?",
                [$telegramId]
            )->getResultArray();

            if ($memberRows === [] && $hasLogTelegram) {
                $history = $db->query(
                    "SELECT no_wa, nama, id_hfm FROM tb_member_logs WHERE TRIM(id_telegram) = ? AND tipe_aktivitas = 'lepas_ib' ORDER BY created_at DESC, id DESC LIMIT 1",
                    [$telegramId]
                )->getRowArray();
                if ($history) $memberRows[] = $history;
            }
            if ($memberRows === []) {
                $memberRows[] = ['no_wa' => null, 'nama' => null, 'id_hfm' => null];
            }

            foreach ($memberRows as $memberRow) {
                $logData = [
                    'no_wa' => $memberRow['no_wa'] ?? null,
                    'nama' => $memberRow['nama'] ?? null,
                    'id_hfm' => $memberRow['id_hfm'] ?? null,
                    'tipe_aktivitas' => 'kick_massal_lepas_ib',
                ];
                if ($hasLogTelegram) $logData['id_telegram'] = $telegramId;
                $db->table('tb_member_logs')->insert($logData);
            }
        }
        if (!$db->transStatus()) {
            $db->transRollback();
            return redirect()->to(base_url('AdminDashboard/memberLogs'))
                ->with('error', 'Kick selesai, tetapi ID Telegram pada riwayat tidak berhasil dipertahankan. Data roster tidak dihapus.');
        }
        $db->table('tb_member_vip')->where('status', 'lepas_ib')->delete();
        $removedFromDashboard = $db->affectedRows();
        if (!$db->transStatus()) {
            $db->transRollback();
            return redirect()->to(base_url('AdminDashboard/memberLogs'))
                ->with('error', 'Kick selesai, tetapi data Lepas IB di dashboard gagal dibersihkan. Riwayat aktivitas tetap tersimpan.');
        }
        $db->transCommit();

        $summary = sprintf(
            'Proses grup %s selesai. Kandidat: %d; berhasil dikeluarkan: %d; dilewati: %d; gagal: %d; data Lepas IB di dashboard dihapus: %d. Riwayat aktivitas tetap tersimpan.',
            (string) ($result['group_name'] ?? 'Telegram VIP'),
            (int) ($result['candidates'] ?? 0),
            (int) ($result['kicked'] ?? 0),
            (int) ($result['skipped'] ?? 0),
            (int) ($result['failed'] ?? 0),
            $removedFromDashboard,
        );
        if ((int) ($result['failed'] ?? 0) > 0) {
            $summary .= ' Periksa log bot dan daftar ban grup; kegagalan setelah ban mungkin memerlukan unban manual.';
        }
        $flashKey = (int) ($result['failed'] ?? 0) > 0 ? 'warning' : 'pesan';
        return redirect()->to(base_url('AdminDashboard/memberLogs'))->with($flashKey, $summary);
    }

    // ==========================================
    // HALAMAN OVERALL PERFORMANCE
    // ==========================================
    public function overallPerformance()
    {
        // 1. Tangkap parameter dari form filter (default 1 bulan terakhir)
        $data = [
            'title'     => 'Overall Performance',
            'date_from' => $this->request->getGet('from_date') ?? date('Y-m-01'),
            'date_to'   => $this->request->getGet('to_date') ?? date('Y-m-d'),
            'group_by'  => $this->request->getGet('group_by') ?? 'day',
            'performance_data' => [],
            'totals'    => [],
            'debug_api' => null
        ];

        // 2. Susun parameter URL
        $queryParams = [
            'from_date' => $data['date_from'],
            'to_date'   => $data['date_to'],
            'group_by'  => $data['group_by']
        ];
        $url = $this->baseUrl . "/performance/overall-performance?" . http_build_query($queryParams);

        // 3. Eksekusi API via cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BOSSCUAN/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 4. Proses response API
        if ($err) {
            $data['debug_api'] = "CURL Error: " . $err;
        } elseif ($response && $httpCode === 200) {
            $apiData = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Ambil data sesuai skema API HFM
                $data['totals'] = $apiData['totals'] ?? [];
                $data['performance_data'] = $apiData['overall_performance'] ?? [];
            } else {
                $data['debug_api'] = "JSON Error: " . json_last_error_msg();
            }
        } elseif ($httpCode === 422) {
            $data['debug_api'] = "Validation Error (422). Format tanggal tidak valid.";
        } else {
            $data['debug_api'] = "Gagal memuat API HFM (HTTP: $httpCode).";
        }

        // 5. Lempar data ke View
        return view('admin_overall_performance', $data);
    }

    // ==========================================
    // HALAMAN CLIENT PERFORMANCE (PRODUCTION)
    // ==========================================
    public function clientPerformance()
    {
        // 1. Naikkan memori limit untuk mencegah OOM saat parsing 50MB JSON
        ini_set('memory_limit', '1024M');

        $data = [
            'title' => 'Client Performance',
            'date_from' => $this->request->getGet('from_date') ?? date('Y-m-01'),
            'date_to' => $this->request->getGet('to_date') ?? date('Y-m-d'),
            'activity_status' => $this->request->getGet('activity_status') ?? '',
            'platform' => $this->request->getGet('platform') ?? '',
            'clients' => [],
            'totals' => [],
            'debug_api' => null,
            'total_semua_klien' => 0
        ];

        // Build URL
        $url = $this->baseUrl . "/performance/client-performance?";
        $params = [];
        if (!empty($data['date_from'])) $params[] = "from_date=" . urlencode($data['date_from']);
        if (!empty($data['date_to'])) $params[] = "to_date=" . urlencode($data['date_to']);
        if (!empty($data['activity_status'])) $params[] = "activity_status=" . urlencode($data['activity_status']);
        if (!empty($data['platform'])) $params[] = "platforms[]=" . urlencode($data['platform']);
        $url .= implode('&', $params);

        // Eksekusi API
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Waktu tunggu 60 detik
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BOSSCUAN/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            $data['debug_api'] = "CURL Error: " . $err;
        } elseif ($response && $httpCode === 200) {
            $apiData = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Ambil summary totals
                $data['totals'] = $apiData['totals'] ?? [];

                // ANTI BROWSER CRASH: Kita hanya mem-passing maksimal 500 baris ke View HTML
                if (isset($apiData['clients']) && is_array($apiData['clients'])) {
                    $data['total_semua_klien'] = count($apiData['clients']);
                    $data['clients'] = array_slice($apiData['clients'], 0, 500);
                }

                // KOSONGKAN MEMORI RAM
                unset($apiData);
                unset($response);
            } else {
                $data['debug_api'] = "JSON Error: " . json_last_error_msg();
            }
        } else {
            $data['debug_api'] = "Gagal memuat API HFM (HTTP: $httpCode).";
        }

        return view('admin_client_performance', $data);
    }

    // ==========================================
    // HALAMAN CLIENT TRADES
    // ==========================================
    public function clientTrades()
    {
        // Naikkan batas memori karena data trades biasanya sangat masif
        ini_set('memory_limit', '1024M');

        // Set default rentang waktu hanya 1 HARI TERAKHIR untuk mencegah Error 413 dari HFM
        $data = [
            'title' => 'Client Trades',
            'date_from' => $this->request->getGet('from_date') ?? date('Y-m-d', strtotime('-1 days')),
            'date_to' => $this->request->getGet('to_date') ?? date('Y-m-d'),
            'platform' => $this->request->getGet('platform') ?? '',
            'trades' => [],
            'totals' => [],
            'debug_api' => null,
            'total_semua_trade' => 0
        ];

        // Build URL Parameter
        $url = $this->baseUrl . "/client-trades/?";
        $params = [];
        if (!empty($data['date_from'])) $params[] = "from_date=" . urlencode($data['date_from']);
        if (!empty($data['date_to'])) $params[] = "to_date=" . urlencode($data['date_to']);
        if (!empty($data['platform'])) $params[] = "platforms[]=" . urlencode($data['platform']);
        $url .= implode('&', $params);

        // Eksekusi cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BOSSCUAN/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Proses Response
        if ($err) {
            $data['debug_api'] = "CURL Error: " . $err;
        } elseif ($httpCode === 413) {
            // Penanganan khusus Error 413 sesuai dokumentasi HFM
            $data['debug_api'] = "Data terlalu besar untuk diproses API HFM. Harap persingkat rentang tanggal pencarian Anda (misal: cari per 1 hari saja).";
        } elseif ($response && $httpCode === 200) {
            $apiData = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $data['totals'] = $apiData['totals'] ?? [];

                // Ambil maksimal 500 data untuk dirender ke View agar browser tidak hang
                if (isset($apiData['trades']) && is_array($apiData['trades'])) {
                    $data['total_semua_trade'] = count($apiData['trades']);
                    $data['trades'] = array_slice($apiData['trades'], 0, 500);
                }

                unset($apiData);
                unset($response);
            } else {
                $data['debug_api'] = "JSON Error: " . json_last_error_msg();
            }
        } elseif ($httpCode === 422) {
            $data['debug_api'] = "Validation Error (422). Format input tidak valid.";
        } else {
            $data['debug_api'] = "Gagal memuat API HFM (HTTP: $httpCode).";
        }

        return view('admin_client_trades', $data);
    }

    // ==========================================
    // HALAMAN COMMISSION GENERATED (CAMPAIGN TRADES)
    // ==========================================
    public function campaignTrades()
    {
        // Naikkan batas memori karena data transaksi bisa sangat besar
        ini_set('memory_limit', '1024M');

        $data = [
            'title' => 'Commission Generated',
            // Default 7 hari terakhir agar data tidak terlalu raksasa
            'date_from' => $this->request->getGet('date_from') ?? date('Y-m-d', strtotime('-7 days')),
            'date_to' => $this->request->getGet('date_to') ?? date('Y-m-d'),
            'selected_campaign' => $this->request->getGet('campaign_ids') ?? '',
            'trades' => [],
            'debug_api' => null,
            'total_semua_trade' => 0,
            // Mengambil daftar campaign dari helper yang sudah ada di file ini
            'campaigns' => $this->getCampaignList()
        ];

        // Build URL Parameter
        $url = $this->baseUrl . "/campaigns/trades?";
        $params = [];
        if (!empty($data['date_from'])) $params[] = "date_from=" . urlencode($data['date_from']);
        if (!empty($data['date_to'])) $params[] = "date_to=" . urlencode($data['date_to']);
        if (!empty($data['selected_campaign'])) $params[] = "campaign_ids=" . urlencode($data['selected_campaign']);

        $url .= implode('&', $params);

        // Eksekusi cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BOSSCUAN/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Proses Response
        if ($err) {
            $data['debug_api'] = "CURL Error: " . $err;
        } elseif ($httpCode === 413) {
            $data['debug_api'] = "Data terlalu besar untuk diproses API HFM. Harap persingkat rentang tanggal pencarian Anda.";
        } elseif ($response && $httpCode === 200) {
            $apiData = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Endpoint ini mengembalikan array transaksi di dalam key "data"
                if (isset($apiData['data']) && is_array($apiData['data'])) {
                    $data['total_semua_trade'] = count($apiData['data']);
                    // Ambil maksimal 500 data untuk dirender ke View agar browser tidak hang
                    $data['trades'] = array_slice($apiData['data'], 0, 500);
                }

                unset($apiData);
                unset($response);
            } else {
                $data['debug_api'] = "JSON Error: " . json_last_error_msg();
            }
        } elseif ($httpCode === 422) {
            $data['debug_api'] = "Validation Error (422). Format tanggal tidak valid.";
        } else {
            $data['debug_api'] = "Gagal memuat API HFM (HTTP: $httpCode).";
        }

        return view('admin_campaign_trades', $data);
    }

    // ==========================================
    // HALAMAN MY CAMPAIGNS
    // ==========================================
    public function campaigns()
    {
        $data = [
            'title' => 'My Campaigns',
            'campaigns' => [],
            'debug_api' => null
        ];

        // Endpoint sesuai dokumentasi terbaru
        $url = $this->baseUrl . "/campaigns/";

        // Eksekusi cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BOSSCUAN/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Proses Response
        if ($err) {
            $data['debug_api'] = "CURL Error: " . $err;
        } elseif ($response && $httpCode === 200) {
            $apiData = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Menangkap data dari skema { "data": [] } atau fallback ke array langsung
                if (isset($apiData['data']) && is_array($apiData['data'])) {
                    $data['campaigns'] = $apiData['data'];
                } elseif (is_array($apiData)) {
                    $data['campaigns'] = $apiData;
                }
            } else {
                $data['debug_api'] = "JSON Error: " . json_last_error_msg();
            }
        } else {
            $data['debug_api'] = "Gagal memuat API HFM (HTTP: $httpCode).";
        }

        return view('admin_campaigns', $data);
    }

    // ==========================================
    // HALAMAN RAW CLICKS (KLIK KAMPANYE)
    // ==========================================
    public function campaignRawClicks()
    {
        // Naikkan batas memori karena data klik bisa sangat masif
        ini_set('memory_limit', '1024M');

        $data = [
            'title' => 'Raw Clicks (Klik Kampanye)',
            // Default 7 hari terakhir
            'date_from' => $this->request->getGet('date_from') ?? date('Y-m-d', strtotime('-7 days')),
            'date_to' => $this->request->getGet('date_to') ?? date('Y-m-d'),
            'selected_campaign' => $this->request->getGet('campaign_ids') ?? '',
            'clicks' => [],
            'debug_api' => null,
            'total_semua_klik' => 0,
            // Mengambil daftar campaign dari helper yang sudah ada
            'campaigns' => $this->getCampaignList()
        ];

        // Build URL Parameter
        $url = $this->baseUrl . "/campaigns/raw-clicks?";
        $params = [];
        if (!empty($data['date_from'])) $params[] = "date_from=" . urlencode($data['date_from']);
        if (!empty($data['date_to'])) $params[] = "date_to=" . urlencode($data['date_to']);
        if (!empty($data['selected_campaign'])) $params[] = "campaign_ids=" . urlencode($data['selected_campaign']);

        $url .= implode('&', $params);

        // Eksekusi cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BOSSCUAN/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Proses Response
        if ($err) {
            $data['debug_api'] = "CURL Error: " . $err;
        } elseif ($httpCode === 413) {
            $data['debug_api'] = "Data terlalu besar untuk diproses API HFM. Harap persingkat rentang tanggal pencarian Anda.";
        } elseif ($response && $httpCode === 200) {
            $apiData = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($apiData['data']) && is_array($apiData['data'])) {
                    $data['total_semua_klik'] = count($apiData['data']);
                    // Batasi data yang dirender ke tabel untuk mencegah browser freeze
                    $data['clicks'] = array_slice($apiData['data'], 0, 500);
                }

                unset($apiData);
                unset($response);
            } else {
                $data['debug_api'] = "JSON Error: " . json_last_error_msg();
            }
        } elseif ($httpCode === 422) {
            $data['debug_api'] = "Validation Error (422). Format tanggal tidak valid.";
        } else {
            $data['debug_api'] = "Gagal memuat API HFM (HTTP: $httpCode).";
        }

        return view('admin_campaign_raw_clicks', $data);
    }

    // ==========================================
    // HALAMAN CAMPAIGN (KAMPANYE)
    // ==========================================
    public function mycampaigns()
    {
        $data['campaigns'] = [];
        $data['debug_api'] = null;
        $data['raw_response'] = null;

        $ch = curl_init($this->baseUrl . "/my-campaigns/");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($ch);
        curl_close($ch);

        // Simpan raw response untuk debug
        $data['raw_response'] = $response;
        $data['http_code'] = $httpCode;
        $data['curl_info'] = $info;

        if ($err) {
            $data['debug_api'] = "CURL Error: " . $err;
        } elseif ($response && $httpCode === 200) {
            $campaignData = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Response langsung array (sesuai dokumentasi)
                if (is_array($campaignData)) {
                    $data['campaigns'] = $campaignData;
                } else {
                    $data['debug_api'] = "Data bukan array: " . gettype($campaignData);
                }
            } else {
                $data['debug_api'] = "JSON Error: " . json_last_error_msg();
            }
        } elseif ($response) {
            $data['debug_api'] = "HTTP Error $httpCode: " . substr($response, 0, 500);
        } else {
            $data['debug_api'] = "Tidak ada response dari API";
        }

        return view('admin_mycampaigns', $data);
    }

    // ==========================================
    // HALAMAN WALLET REGISTRATIONS
    // ==========================================
    public function campaignWallets($campaignId = null)
    {
        $data = [
            'campaign_id' => $campaignId,
            'wallets' => [],
            'summary' => [],
            'debug_api' => null,
            'campaigns' => [],
            'raw_response' => null,
            'date_from' => $this->request->getGet('date_from') ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $this->request->getGet('date_to') ?? date('Y-m-d'),
            'selected_campaign' => $this->request->getGet('campaign_ids') ?? $campaignId ?? '',
        ];

        // Ambil daftar campaign untuk dropdown (REAL DATA dari API)
        $data['campaigns'] = $this->getCampaignList();

        // Build URL
        $url = $this->baseUrl . "/campaigns/wallets?";
        $params = [];

        if (!empty($data['date_from'])) {
            $params[] = "date_from=" . urlencode($data['date_from']);
        }
        if (!empty($data['date_to'])) {
            $params[] = "date_to=" . urlencode($data['date_to']);
        }
        if (!empty($data['selected_campaign'])) {
            $params[] = "campaign_ids=" . urlencode($data['selected_campaign']);
        }
        $url .= implode('&', $params);

        // Request ke API
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data['raw_response'] = $response;
        $data['http_code'] = $httpCode;

        if ($err) {
            $data['debug_api'] = "CURL Error: " . $err;
        } elseif ($response && $httpCode === 200) {
            $walletData = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Cek struktur response
                if (isset($walletData['data']) && is_array($walletData['data'])) {
                    $data['wallets'] = $walletData['data'];
                } elseif (is_array($walletData)) {
                    $data['wallets'] = $walletData;
                } else {
                    $data['debug_api'] = "Data wallet tidak valid: " . gettype($walletData);
                }

                if (!empty($data['wallets'])) {
                    $data['summary'] = $this->generateWalletSummary($data['wallets']);
                }
            } else {
                $data['debug_api'] = "JSON Error: " . json_last_error_msg();
            }
        } elseif ($response) {
            $data['debug_api'] = "HTTP Error $httpCode: " . substr($response, 0, 500);
        } else {
            $data['debug_api'] = "Tidak ada response dari API";
        }

        return view('admin_campaign_wallets', $data);
    }

    // ==========================================
    // SYNC HFM (AJAX)
    // ==========================================
    public function syncHfm($id_hfm)
    {
        $db = \Config\Database::connect();

        $member = $db->table('tb_member_vip')
            ->where('id_hfm', $id_hfm)
            ->get()
            ->getRowArray();

        if (!$member) {
            return $this->response->setJSON([
                'status' => 'error',
                'pesan' => 'Data lokal tidak ditemukan'
            ]);
        }

        $url = $this->baseUrl . "/clients/" . rawurlencode($member['id_hfm']) . "/report";
        $response = false;
        $httpCode = 0;
        $curlError = '';
        $curlErrno = 0;

        // HFM kadang lambat merespons. Pisahkan batas koneksi dari batas response,
        // paksa IPv4 (menghindari route IPv6 VPS yang menggantung), dan ulangi
        // sekali hanya untuk kegagalan jaringan sementara.
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Accept: application/json',
                ],
                CURLOPT_ENCODING => '',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'BOSSCUAN-HFM-Sync/1.0',
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            if ($response !== false || $curlErrno === 0 || $attempt === 2) {
                break;
            }
            usleep(250000);
        }

        if ($response && $httpCode === 200) {
            $rawData = json_decode($response, true);
            $dataApi = [];

            // Membaca format JSON HFM
            if (isset($rawData[0]) && is_array($rawData[0])) {
                $dataApi = $rawData[0];
            } elseif (isset($rawData['data']) && is_array($rawData['data'])) {
                $dataApi = $rawData['data'];
            } else {
                $dataApi = $rawData;
            }

            if (isset($dataApi['id']) && $dataApi['id'] != null) {
                // Kalkulasi deposit tertinggi (Deposits, Balance, atau Equity)
                $depo   = (float)($dataApi['deposits'] ?? 0);
                $bal    = (float)($dataApi['balance'] ?? 0);
                $equity = (float)($dataApi['equity'] ?? 0);
                $deposit_real = max($depo, $bal, $equity);

                // Bersihkan string Last Trade dari milidetik
                $last_trade_real = null;
                $raw_last_trade = $dataApi['last_trade'] ?? 'N/A';
                if (!empty($raw_last_trade) && $raw_last_trade !== 'N/A' && preg_match('/^20\d{2}/', $raw_last_trade)) {
                    $last_trade_real = date('Y-m-d H:i:s', strtotime(substr($raw_last_trade, 0, 19)));
                }

                $updateData = [
                    'nama'       => $dataApi['name'] ?? 'Member BOSSCUAN',
                    'currency'   => $dataApi['account_currency'] ?? 'USD',
                    'deposit'    => $deposit_real,
                    'status'     => 'aktif',
                    'last_trade' => $last_trade_real
                ];
                $apiEmail = trim((string) ($dataApi['email'] ?? ''));
                if ($apiEmail !== '') {
                    $updateData['email'] = $apiEmail;
                }
                if (array_key_exists('campaign', $dataApi)) {
                    $campaign = trim((string) ($dataApi['campaign'] ?? ''));
                    $updateData['campaign'] = $campaign !== '' ? $campaign : null;
                }

                // PENCATATAN LOG: Jika status berubah jadi aktif
                if ($member['status'] !== 'aktif') {
                    $db->table('tb_member_logs')->insert([
                        'no_wa'          => $member['no_wa'],
                        'nama'           => $member['nama'],
                        'id_hfm'         => $member['id_hfm'],
                        'id_telegram'    => $member['id_telegram'] ?? null,
                        'tipe_aktivitas' => 'aktif_kembali'
                    ]);
                }

                $db->table('tb_member_vip')->where('id_hfm', $id_hfm)->update($updateData);

                return $this->response->setJSON([
                    'status' => 'sukses',
                    'data' => $updateData
                ]);
            } else {
                // Sync hanya memperbarui status. Penghapusan roster dilakukan
                // khusus oleh tombol Kick pada halaman Member Logs.
                $hasLogTelegram = $this->hasMemberLogTelegramColumn($db);
                $logData = [
                    'no_wa'          => $member['no_wa'],
                    'nama'           => $member['nama'],
                    'id_hfm'         => $member['id_hfm'],
                    'tipe_aktivitas' => 'lepas_ib',
                ];
                if ($hasLogTelegram) {
                    $logData['id_telegram'] = $member['id_telegram'] ?? null;
                }

                $db->transBegin();
                $existingLog = $db->table('tb_member_logs')
                    ->where('id_hfm', $member['id_hfm'])
                    ->orderBy('created_at', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->get()->getRowArray();
                if (!$existingLog || ($existingLog['tipe_aktivitas'] ?? '') !== 'lepas_ib') {
                    $db->table('tb_member_logs')->insert($logData);
                } elseif ($hasLogTelegram && in_array(trim((string) ($existingLog['id_telegram'] ?? '')), ['', '-'], true) && !empty($member['id_telegram'])) {
                    $db->table('tb_member_logs')->where('id', $existingLog['id'])->update([
                        'id_telegram' => $member['id_telegram'],
                    ]);
                }

                $db->table('tb_member_vip')->where('id_hfm', $id_hfm)->update(['status' => 'lepas_ib']);
                $memberStillExists = $db->table('tb_member_vip')->where('id_hfm', $id_hfm)->countAllResults();
                if (!$db->transStatus() || $memberStillExists < 1) {
                    $db->transRollback();
                    return $this->response->setStatusCode(500)->setJSON([
                        'status' => 'error',
                        'pesan' => 'Status Lepas IB terdeteksi, tetapi status gagal disimpan. Data peserta tidak dihapus.',
                    ]);
                }
                $db->transCommit();

                return $this->response->setJSON(['status' => 'lepas_ib']);
            }
        }

        $detail = $curlError !== ''
            ? "cURL #{$curlErrno}: {$curlError}"
            : 'Respons kosong atau HTTP bukan 200 dari API HFM';
        return $this->response->setJSON([
            'status' => 'error',
            'pesan' => 'Gagal koneksi ke HFM (HTTP: ' . $httpCode . '). ' . $detail
        ]);
    }

    // ==========================================
    // UPDATE MEMBER (MANUAL OLEH ADMIN)
    // ==========================================
    public function updateMember()
    {
        // Tangkap ID Lama dan ID Baru
        $old_id_hfm = $this->request->getPost('old_id_hfm');
        $new_id_hfm = $this->request->getPost('id_hfm');
        $statusBaru = $this->request->getPost('status');

        $db = \Config\Database::connect();

        // Ambil data member lama sebelum diupdate berdasarkan ID LAMA
        $memberLama = $db->table('tb_member_vip')->where('id_hfm', $old_id_hfm)->get()->getRowArray();

        $dataUpdate = [
            'id_hfm'     => $new_id_hfm, // Ini akan memperbarui ID HFM yang salah
            'no_wa'      => $this->request->getPost('no_wa'),
            'currency'   => $this->request->getPost('currency'),
            'deposit'    => $this->request->getPost('deposit'),
            'status'     => $statusBaru,
            'last_trade' => $this->request->getPost('last_trade'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // LOGIKA PENCATATAN LOG: JIKA ADA PERUBAHAN STATUS MANUAL
        if ($memberLama && $memberLama['status'] !== $statusBaru) {
            $tipeAktivitas = 'ubah_status'; // default
            if ($statusBaru == 'aktif') $tipeAktivitas = 'aktif_kembali';
            if ($statusBaru == 'lepas_ib') $tipeAktivitas = 'lepas_ib';
            if ($statusBaru == 'keluar_grup') $tipeAktivitas = 'keluar_grup';

            $db->table('tb_member_logs')->insert([
                'no_wa'          => $memberLama['no_wa'],
                'nama'           => $memberLama['nama'],
                'id_hfm'         => $new_id_hfm, // Gunakan ID baru untuk log
                'tipe_aktivitas' => $tipeAktivitas
            ]);
        }

        // UPDATE TABEL MEMBER VIP
        $db->table('tb_member_vip')->where('id_hfm', $old_id_hfm)->update($dataUpdate);

        // BONUS: Update juga riwayat log mereka agar nyambung dengan ID yang baru
        if ($old_id_hfm !== $new_id_hfm) {
            $db->table('tb_member_logs')->where('id_hfm', $old_id_hfm)->update(['id_hfm' => $new_id_hfm]);
        }

        session()->setFlashdata('pesan', 'Data member berhasil diupdate.');
        return redirect()->to(base_url('AdminDashboard'));
    }

    // ==========================================
    // UPDATE ID TRADING CEPAT DARI HALAMAN LOG
    // ==========================================
    public function updateIdQuick()
    {
        $old_id_hfm = $this->request->getPost('old_id_hfm');
        $new_id_hfm = $this->request->getPost('id_hfm');

        if (empty($old_id_hfm) || empty($new_id_hfm)) {
            session()->setFlashdata('pesan', 'ID Trading tidak boleh kosong!');
            return redirect()->back();
        }

        $db = \Config\Database::connect();

        // 1. Update ID di tabel member VIP utama
        $db->table('tb_member_vip')->where('id_hfm', $old_id_hfm)->update([
            'id_hfm' => $new_id_hfm,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // 2. Update ID di seluruh riwayat Log mereka agar tetap terhubung
        $db->table('tb_member_logs')->where('id_hfm', $old_id_hfm)->update([
            'id_hfm' => $new_id_hfm
        ]);

        session()->setFlashdata('pesan', 'ID Trading berhasil diperbarui dari halaman Log!');
        return redirect()->back();
    }

    // ==========================================
    // DASHBOARD VIP (ALTERNATIF)
    // ==========================================
    public function dashboardVip()
    {
        $db = \Config\Database::connect();

        $data['members'] = $db->table('tb_member_vip')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('admin_dashboard', $data);
    }

    // ==========================================
    // UPDATE MEMBER (VIA AJAX - TANPA RELOAD)
    // ==========================================
    public function updateMemberAjax()
    {
        $old_id_hfm = $this->request->getPost('old_id_hfm');
        $new_id_hfm = $this->request->getPost('id_hfm');
        $statusBaru = $this->request->getPost('status');

        // 1. Validasi ID tidak boleh kosong
        if (empty($old_id_hfm) || empty($new_id_hfm)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'pesan' => 'ID HFM tidak boleh kosong'
            ]);
        }

        // 2. Amankan format tanggal agar tidak menyebabkan Error 500 di Database
        $last_trade = $this->request->getPost('last_trade');
        if (empty($last_trade) || $last_trade == '') {
            $last_trade = '0000-00-00 00:00:00';
        }

        $db = \Config\Database::connect();

        // 3. Gunakan Try-Catch agar jika database error, sistem tidak mati (Error 500), 
        // melainkan mengirim pesan error ke Javascript
        try {
            $memberLama = $db->table('tb_member_vip')->where('id_hfm', $old_id_hfm)->get()->getRowArray();

            $dataUpdate = [
                'id_hfm'     => $new_id_hfm,
                'no_wa'      => $this->request->getPost('no_wa'),
                'currency'   => $this->request->getPost('currency'),
                'deposit'    => $this->request->getPost('deposit'),
                'status'     => $statusBaru,
                'last_trade' => $last_trade,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Pencatatan Log
            if ($memberLama && $memberLama['status'] !== $statusBaru) {
                $tipeAktivitas = 'ubah_status';
                if ($statusBaru == 'aktif') $tipeAktivitas = 'aktif_kembali';
                if ($statusBaru == 'lepas_ib') $tipeAktivitas = 'lepas_ib';
                if ($statusBaru == 'keluar_grup') $tipeAktivitas = 'keluar_grup';

                $db->table('tb_member_logs')->insert([
                    'no_wa'          => $memberLama['no_wa'],
                    'nama'           => $memberLama['nama'],
                    'id_hfm'         => $new_id_hfm,
                    'tipe_aktivitas' => $tipeAktivitas
                ]);
            }

            // Eksekusi Update
            $db->table('tb_member_vip')->where('id_hfm', $old_id_hfm)->update($dataUpdate);

            // Update log ID HFM jika berubah
            if ($old_id_hfm !== $new_id_hfm) {
                $db->table('tb_member_logs')->where('id_hfm', $old_id_hfm)->update(['id_hfm' => $new_id_hfm]);
            }

            return $this->response->setJSON([
                'status' => 'sukses',
                'pesan' => 'Data member berhasil diupdate.'
            ]);
        } catch (\Exception $e) {
            // Tangkap pesan error dari database dan kembalikan ke JS
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'pesan' => 'Database Error: ' . $e->getMessage()
            ]);
        }
    }

    // ==========================================
    // UPDATE MEMBER
    // ==========================================
    public function updateMember1()
    {
        $id_hfm = $this->request->getPost('id_hfm');

        $dataUpdate = [
            'no_wa'      => $this->request->getPost('no_wa'),
            'currency'   => $this->request->getPost('currency'),
            'deposit'    => $this->request->getPost('deposit'),
            'status'     => $this->request->getPost('status'),
            'last_trade' => $this->request->getPost('last_trade'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $db = \Config\Database::connect();
        $db->table('tb_member_vip')
            ->where('id_hfm', $id_hfm)
            ->update($dataUpdate);

        session()->setFlashdata('pesan', 'Data member berhasil diupdate.');
        return redirect()->to(base_url('admin_dashboard'));
    }

    // ==========================================
    // HAPUS MEMBER (DENGAN PENCEGAHAN KICK JIKA DUPLIKAT)
    // ==========================================
    public function hapusMember($id_hfm)
    {
        $db = \Config\Database::connect();

        $cek = $db->table('tb_member_vip')
            ->where('id_hfm', $id_hfm)
            ->get()
            ->getRowArray();

        if (!$cek) {
            session()->setFlashdata('pesan', 'Data tidak ditemukan!');
            return redirect()->to(base_url('AdminDashboard'));
        }

        // 1. CEK APAKAH ADA DATA DUPLIKAT YANG MASIH AKTIF
        // Cari apakah user ini punya baris data lain dengan ID HFM yang berbeda
        $duplikat = $db->table('tb_member_vip')
            ->groupStart()
            ->where('no_wa', $cek['no_wa'])
            ->orWhere('id_telegram', $cek['id_telegram'])
            ->groupEnd()
            ->where('id_hfm !=', $id_hfm) // Selain ID yang sedang dihapus
            ->countAllResults();

        $pesanKick = "";

        // 2. KIRIM KICK KE TELEGRAM (HANYA JIKA TIDAK ADA DUPLIKAT)
        if ($duplikat == 0) {
            if (!empty($cek['id_telegram']) && $cek['id_telegram'] !== '-') {
                $hasilKick = $this->postBotJson('/kick-telegram', ['id_telegram' => $cek['id_telegram']]);
                $pesanKick = $hasilKick['ok'] ? ' & member otomatis di-kick dari Telegram.' : ' (kick Telegram gagal: ' . $hasilKick['message'] . ').';
            }
        } else {
            // Jika ada duplikat, batalkan kick agar akun utamanya tidak terhapus dari grup
            $pesanKick = " (Member TIDAK di-kick karena masih ada data aslinya).";
        }

        // 3. CATAT KE LOG SEBELUM DIHAPUS
        $logData = [
            'no_wa'          => $cek['no_wa'],
            'nama'           => $cek['nama'],
            'id_hfm'         => $cek['id_hfm'],
            'tipe_aktivitas' => 'keluar_di_remove',
        ];
        if ($this->hasMemberLogTelegramColumn($db)) {
            $logData['id_telegram'] = $cek['id_telegram'] ?? null;
        }
        $db->table('tb_member_logs')->insert($logData);

        // 4. HAPUS DATA DARI DATABASE
        $db->table('tb_member_vip')
            ->where('id_hfm', $id_hfm)
            ->delete();

        session()->setFlashdata('pesan', 'Data berhasil dibersihkan' . $pesanKick);
        return redirect()->to(base_url('AdminDashboard'));
    }

    // ==========================================
    // HAPUS MEMBER & OTOMATIS KICK DARI GRUP
    // ==========================================
    public function hapusMember1($id_hfm)
    {
        $db = \Config\Database::connect();

        $cek = $db->table('tb_member_vip')
            ->where('id_hfm', $id_hfm)
            ->get()
            ->getRowArray();

        if (!$cek) {
            session()->setFlashdata('pesan', 'Data tidak ditemukan!');
            return redirect()->to(base_url('AdminDashboard'));
        }

        // 1. KIRIM PERINTAH KICK KE BOT NODE.JS
        // Pastikan URL ini sesuai dengan port tempat bot berjalan (misal: 3000)
        $urlBot = "http://127.0.0.1:3000/kick-member";

        $ch = curl_init($urlBot);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['nomor' => $cek['no_wa']]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout 5 detik agar tidak menghambat web

        curl_exec($ch);
        curl_close($ch);

        // 2. CATAT KE LOG SEBELUM DIHAPUS
        $logData = [
            'no_wa'          => $cek['no_wa'],
            'nama'           => $cek['nama'],
            'id_hfm'         => $cek['id_hfm'],
            'tipe_aktivitas' => 'keluar_di_remove',
        ];
        if ($this->hasMemberLogTelegramColumn($db)) {
            $logData['id_telegram'] = $cek['id_telegram'] ?? null;
            $telegramId = trim((string) ($cek['id_telegram'] ?? ''));
            if ($telegramId !== '' && $telegramId !== '-') {
                $db->table('tb_member_logs')
                    ->where('id_hfm', $cek['id_hfm'])
                    ->groupStart()
                    ->where('id_telegram IS NULL', null, false)
                    ->orWhere('id_telegram', '')
                    ->orWhere('id_telegram', '-')
                    ->groupEnd()
                    ->update(['id_telegram' => $telegramId]);
            }
        }
        $db->table('tb_member_logs')->insert($logData);

        // 3. HAPUS DATA DARI DATABASE
        $db->table('tb_member_vip')
            ->where('id_hfm', $id_hfm)
            ->delete();

        session()->setFlashdata('pesan', 'Data berhasil dihapus & member otomatis di-kick dari WA.');
        return redirect()->to(base_url('AdminDashboard'));
    }

    private function hasMemberLogTelegramColumn($db): bool
    {
        return (bool) $db->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_member_logs' AND COLUMN_NAME = 'id_telegram' LIMIT 1"
        )->getRow();
    }

    private function countUniqueLoggedTradingAccounts($db, ?array $types = null, ?string $createdSince = null, ?string $createdUntil = null): int
    {
        // id_hfm adalah ID trading dan boleh tersimpan sebagai string; trim membuat
        // nilai kosong/berisi spasi tidak ikut dihitung sebagai satu akun.
        $tradingExpr = "NULLIF(TRIM(tb_member_logs.id_hfm), '')";
        $builder = $db->table('tb_member_logs')
            ->select("COUNT(DISTINCT {$tradingExpr}) AS total", false)
            ->where("{$tradingExpr} IS NOT NULL", null, false)
            ->where("{$tradingExpr} <> '-'", null, false);

        if ($types !== null) {
            $builder->whereIn('tb_member_logs.tipe_aktivitas', $types);
        }
        if ($createdSince !== null) {
            $builder->where('tb_member_logs.created_at >=', $createdSince);
        }
        if ($createdUntil !== null) {
            $builder->where('tb_member_logs.created_at <=', $createdUntil);
        }

        return (int) ($builder->get()->getRowArray()['total'] ?? 0);
    }

    private function countLePasIbKickCandidates($db, bool $hasLogTelegram, string $groupId): int
    {
        if ($groupId === '') {
            return 0;
        }

        if (!$hasLogTelegram) {
            $row = $db->query(
                "SELECT COUNT(DISTINCT TRIM(id_telegram)) AS total
                 FROM tb_member_vip
                 WHERE status = 'lepas_ib'
                   AND id_telegram IS NOT NULL
                   AND TRIM(id_telegram) REGEXP '^[0-9]+$'
                   AND NOT EXISTS (
                     SELECT 1 FROM telegram_group_members AS membership
                     WHERE membership.telegram_id = TRIM(tb_member_vip.id_telegram)
                       AND membership.group_id = ? AND membership.status = 'left'
                   )",
                [$groupId]
            )->getRowArray();
            return (int) ($row['total'] ?? 0);
        }

        $row = $db->query(
            "SELECT COUNT(DISTINCT candidates.telegram_id) AS total
             FROM (
               SELECT TRIM(id_telegram) AS telegram_id
               FROM tb_member_vip
                 WHERE status = 'lepas_ib'
                   AND id_telegram IS NOT NULL
                   AND TRIM(id_telegram) REGEXP '^[0-9]+$'
                   AND NOT EXISTS (
                     SELECT 1 FROM telegram_group_members AS membership
                     WHERE membership.telegram_id = TRIM(tb_member_vip.id_telegram)
                       AND membership.group_id = ? AND membership.status = 'left'
                   )
               UNION
               SELECT TRIM(logs.id_telegram) AS telegram_id
               FROM tb_member_logs AS logs
               WHERE logs.tipe_aktivitas = 'lepas_ib'
                 AND logs.id_telegram IS NOT NULL
                 AND TRIM(logs.id_telegram) REGEXP '^[0-9]+$'
                 AND NOT EXISTS (
                   SELECT 1 FROM telegram_group_members AS membership
                   WHERE membership.telegram_id = TRIM(logs.id_telegram)
                     AND membership.group_id = ? AND membership.status = 'left'
                 )
                 AND NOT EXISTS (
                   SELECT 1 FROM tb_member_logs AS newer
                   WHERE TRIM(newer.id_telegram) = TRIM(logs.id_telegram)
                     AND newer.id > logs.id
                 )
                 AND NOT EXISTS (
                   SELECT 1 FROM tb_member_vip AS current_member
                   WHERE TRIM(current_member.id_telegram) = TRIM(logs.id_telegram)
                     AND current_member.status = 'aktif'
                 )
             ) AS candidates",
            [$groupId, $groupId]
        )->getRowArray();
        return (int) ($row['total'] ?? 0);
    }

    // ==========================================
    // HAPUS LOG AKTIVITAS
    // ==========================================
    public function hapusLog($id)
    {
        $db = \Config\Database::connect();

        // 1. Ambil data log berdasarkan ID log tersebut
        $log = $db->table('tb_member_logs')->where('id', $id)->get()->getRowArray();

        if ($log) {
            // 2. KIRIM PERINTAH KICK KE BOT NODE.JS
            // Kita gunakan no_wa dari log tersebut
            $urlBot = "http://127.0.0.1:3000/kick-member";
            $ch = curl_init($urlBot);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['nomor' => $log['no_wa']]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);

            // 3. Hapus Log-nya
            $db->table('tb_member_logs')->where('id', $id)->delete();

            session()->setFlashdata('pesan', 'Log dihapus & member otomatis di-kick dari WA!');
        }

        return redirect()->back();
    }

    // ==========================================
    // FUNGSI PRIVATE / HELPER
    // ==========================================

    /**
     * Generate summary dari data wallets
     */
    private function generateWalletSummary($wallets)
    {
        $summary = [
            'total' => count($wallets),
            'active' => 0,
            'inactive' => 0,
            'total_deposit' => 0,
            'by_currency' => [],
            'by_campaign' => [],
            'by_date' => [],
        ];

        foreach ($wallets as $wallet) {
            // Status
            if (isset($wallet['status']) && $wallet['status'] === 'active') {
                $summary['active']++;
            } else {
                $summary['inactive']++;
            }

            // Deposit
            if (isset($wallet['deposit'])) {
                $summary['total_deposit'] += floatval($wallet['deposit']);
            }

            // By Currency
            if (isset($wallet['currency'])) {
                $curr = $wallet['currency'];
                $summary['by_currency'][$curr] = ($summary['by_currency'][$curr] ?? 0) + 1;
            }

            // By Campaign
            if (isset($wallet['campaign_id'])) {
                $campId = $wallet['campaign_id'];
                $summary['by_campaign'][$campId] = ($summary['by_campaign'][$campId] ?? 0) + 1;
            }

            // By Date
            if (isset($wallet['created_at'])) {
                $date = substr($wallet['created_at'], 0, 10);
                $summary['by_date'][$date] = ($summary['by_date'][$date] ?? 0) + 1;
            }
        }

        return $summary;
    }

    /**
     * Ambil daftar campaign REAL dari API
     * TANPA HARDCODE
     */
    private function getCampaignList()
    {
        $url = $this->baseUrl . "/my-campaigns/";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $campaigns = [];

        if (!$error && $response && $httpCode === 200) {
            $data = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                foreach ($data as $camp) {
                    // Gunakan 'name' sebagai ID karena API tidak memberikan field 'id'
                    $campId = $camp['name'] ?? '';
                    if (!empty($campId)) {
                        $campaigns[] = [
                            'id' => $campId,
                            'name' => $camp['name'] ?? 'Unnamed Campaign'
                        ];
                    }
                }
            }
        }

        return $campaigns;
    }

    /**
     * TEST API - Untuk debugging
     */
    public function testApi()
    {
        $urls = [
            'Campaigns' => $this->baseUrl . "/my-campaigns/",
            'Wallets' => $this->baseUrl . "/campaigns/wallets?date_from=" . date('Y-m-d', strtotime('-7 days')),
        ];

        $results = [];

        foreach ($urls as $name => $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            $results[$name] = [
                'url' => $url,
                'http_code' => $httpCode,
                'error' => $error,
                'response_length' => strlen($response ?? ''),
                'response_preview' => $response ? substr($response, 0, 1000) : null,
                'is_json' => $response ? json_decode($response) !== null : false,
                'curl_info' => $info
            ];
        }

        echo "<h2>API Test Results</h2>";
        echo "<pre>";
        print_r($results);
        echo "</pre>";
    }
}
