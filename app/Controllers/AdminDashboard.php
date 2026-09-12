<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class AdminDashboard extends BaseController
{
    private $apiKey = "127e07f2-3b2a-4cb5-9a5b-0610e4ecc86e";
    private $baseUrl = "https://api.hfm-partners.com/api";

    // ==========================================
    // DASHBOARD UTAMA
    // ==========================================
    public function index()
    {
        $db = \Config\Database::connect();

        $hariIni = date('Y-m-d');
        $kemarin = date('Y-m-d', strtotime('-1 day'));
        $batasPasif = date('Y-m-d', strtotime('-30 days'));

        $data['aktif_total'] = $db->table('tb_member_vip')->where('status', 'aktif')->countAllResults();
        $data['baru_hari_ini'] = $db->table('tb_member_vip')->like('created_at', $hariIni)->countAllResults();
        $data['baru_kemarin']  = $db->table('tb_member_vip')->like('created_at', $kemarin)->countAllResults();

        $data['lepas_hari_ini'] = $db->table('tb_member_vip')->where('status', 'lepas_ib')->like('updated_at', $hariIni)->countAllResults();
        $data['lepas_kemarin']  = $db->table('tb_member_vip')->where('status', 'lepas_ib')->like('updated_at', $kemarin)->countAllResults();

        $data['pasif_total'] = $db->table('tb_member_vip')
            ->where('status', 'aktif')
            ->where('last_trade <=', $batasPasif)
            ->countAllResults();

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

        // Ambil parameter filter
        $search = $this->request->getGet('search') ?? '';
        $tipe = $this->request->getGet('tipe') ?? '';
        $status = $this->request->getGet('status') ?? ''; // Filter Status Terakhir dari Dashboard Utama
        $date_from = $this->request->getGet('date_from') ?? '';
        $date_to = $this->request->getGet('date_to') ?? '';
        $page = $this->request->getGet('page') ?? 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Build query
        $builder = $db->table('tb_member_logs');
        $builder->select('tb_member_logs.*, tb_member_vip.status as status_member, tb_member_vip.id_telegram');

        // 1. SUBQUERY: Mengunci agar hanya mengambil aktivitas paling TERAKHIR per member (Berdasarkan No WA)
        $subquery = "(SELECT no_wa, MAX(created_at) as max_date FROM tb_member_logs GROUP BY no_wa)";
        $builder->join("$subquery sub", "sub.no_wa = tb_member_logs.no_wa AND sub.max_date = tb_member_logs.created_at", "inner");

        // Hubungkan ke tabel VIP menggunakan ID HFM atau nomor WA
        $builder->join('tb_member_vip', 'tb_member_vip.id_hfm = tb_member_logs.id_hfm OR tb_member_vip.no_wa = tb_member_logs.no_wa', 'left');
        $builder->orderBy('tb_member_logs.created_at', 'DESC');

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
            $builder->where('tb_member_vip.status', $status);
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
        $totalPages = ceil($totalData / $perPage);

        // Ambil data dengan pagination
        $data['logs'] = $builder->get($perPage, $offset)->getResultArray();

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

        // ====================================================================
        // PERBAIKAN STATISTIK KARTU: BERDASARKAN ID TELEGRAM UNIK (BALANCE)
        // ====================================================================

        // 1. Total User Ber-ID Telegram Unik yang memiliki log aktivitas
        $qTotal = $db->table('tb_member_logs')
            ->select('COUNT(DISTINCT tb_member_vip.id_telegram) as total')
            ->join('tb_member_vip', 'tb_member_vip.id_hfm = tb_member_logs.id_hfm OR tb_member_vip.no_wa = tb_member_logs.no_wa', 'left')
            ->where('tb_member_vip.id_telegram !=', '')
            ->where('tb_member_vip.id_telegram !=', '-')
            ->where('tb_member_vip.id_telegram IS NOT NULL')
            ->get()->getRowArray();

        // 2. Total User Unik yang Masuk / Daftar (Menghitung variasi nama log baru)
        $qMasuk = $db->table('tb_member_logs')
            ->select('COUNT(DISTINCT tb_member_vip.id_telegram) as total')
            ->join('tb_member_vip', 'tb_member_vip.id_hfm = tb_member_logs.id_hfm OR tb_member_vip.no_wa = tb_member_logs.no_wa', 'left')
            ->where('tb_member_vip.id_telegram !=', '')
            ->where('tb_member_vip.id_telegram !=', '-')
            ->where('tb_member_vip.id_telegram IS NOT NULL')
            ->whereIn('tb_member_logs.tipe_aktivitas', ['masuk', 'masuk_id_baru', 'aktif_kembali', 'masuk_via_form'])
            ->get()->getRowArray();

        // 3. Total User Unik yang Keluar / Lepas IB / Di-kick Admin
        $qKeluar = $db->table('tb_member_logs')
            ->select('COUNT(DISTINCT tb_member_vip.id_telegram) as total')
            ->join('tb_member_vip', 'tb_member_vip.id_hfm = tb_member_logs.id_hfm OR tb_member_vip.no_wa = tb_member_logs.no_wa', 'left')
            ->where('tb_member_vip.id_telegram !=', '')
            ->where('tb_member_vip.id_telegram !=', '-')
            ->where('tb_member_vip.id_telegram IS NOT NULL')
            ->whereIn('tb_member_logs.tipe_aktivitas', ['keluar_sendiri', 'keluar_di_remove', 'lepas_ib', 'keluar_grup'])
            ->get()->getRowArray();

        // 4. Total User Unik yang memiliki aktivitas apa pun Hari Ini
        $qHariIni = $db->table('tb_member_logs')
            ->select('COUNT(DISTINCT tb_member_vip.id_telegram) as total')
            ->join('tb_member_vip', 'tb_member_vip.id_hfm = tb_member_logs.id_hfm OR tb_member_vip.no_wa = tb_member_logs.no_wa', 'left')
            ->where('tb_member_vip.id_telegram !=', '')
            ->where('tb_member_vip.id_telegram !=', '-')
            ->where('tb_member_vip.id_telegram IS NOT NULL')
            ->where('tb_member_logs.created_at >=', date('Y-m-d 00:00:00'))
            ->get()->getRowArray();

        $data['stats'] = [
            'total'    => $qTotal['total'] ?? 0,
            'masuk'    => $qMasuk['total'] ?? 0,
            'keluar'   => $qKeluar['total'] ?? 0,
            'hari_ini' => $qHariIni['total'] ?? 0,
        ];

        return view('admin_member_logs', $data);
    }

    // ==========================================
    // HALAMAN CAMPAIGN (KAMPANYE)
    // ==========================================
    public function campaigns()
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

        return view('admin_campaigns', $data);
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

        $url = $this->baseUrl . "/clients/" . $member['id_hfm'] . "/report";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ]);
        // Matikan verifikasi SSL agar request tidak diblokir/timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

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
                $last_trade_real = '0000-00-00 00:00:00';
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

                // PENCATATAN LOG: Jika status berubah jadi aktif
                if ($member['status'] !== 'aktif') {
                    $db->table('tb_member_logs')->insert([
                        'no_wa'          => $member['no_wa'],
                        'nama'           => $member['nama'],
                        'id_hfm'         => $member['id_hfm'],
                        'tipe_aktivitas' => 'aktif_kembali'
                    ]);
                }

                $db->table('tb_member_vip')->where('id_hfm', $id_hfm)->update($updateData);

                return $this->response->setJSON([
                    'status' => 'sukses',
                    'data' => $updateData
                ]);
            } else {
                // PENCATATAN LOG: Jika tidak valid/lepas IB
                if ($member['status'] !== 'lepas_ib') {
                    $db->table('tb_member_logs')->insert([
                        'no_wa'          => $member['no_wa'],
                        'nama'           => $member['nama'],
                        'id_hfm'         => $member['id_hfm'],
                        'tipe_aktivitas' => 'lepas_ib'
                    ]);
                }

                $db->table('tb_member_vip')->where('id_hfm', $id_hfm)->update(['status' => 'lepas_ib']);

                return $this->response->setJSON(['status' => 'lepas_ib']);
            }
        }

        return $this->response->setJSON([
            'status' => 'error',
            'pesan' => 'Gagal koneksi ke HFM (HTTP: ' . $httpCode . ')'
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

        // 2. KIRIM PERINTAH KICK KE BOT (HANYA JIKA TIDAK ADA DUPLIKAT)
        if ($duplikat == 0 && !empty($cek['id_telegram']) && $cek['id_telegram'] !== '-') {
            $urlBot = "http://127.0.0.1:3000/kick-telegram";

            $ch = curl_init($urlBot);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['id_telegram' => $cek['id_telegram']]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            curl_exec($ch);
            curl_close($ch);

            $pesanKick = " & member otomatis di-kick dari Telegram.";
        } else {
            // Jika ada duplikat, batalkan kick agar akun utamanya tidak terhapus dari grup
            $pesanKick = " (Member TIDAK di-kick karena masih ada data aslinya).";
        }

        // 3. CATAT KE LOG SEBELUM DIHAPUS
        $db->table('tb_member_logs')->insert([
            'no_wa'          => $cek['no_wa'],
            'nama'           => $cek['nama'],
            'id_hfm'         => $cek['id_hfm'],
            'tipe_aktivitas' => 'keluar_di_remove'
        ]);

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
        $db->table('tb_member_logs')->insert([
            'no_wa'          => $cek['no_wa'],
            'nama'           => $cek['nama'],
            'id_hfm'         => $cek['id_hfm'],
            'tipe_aktivitas' => 'keluar_di_remove'
        ]);

        // 3. HAPUS DATA DARI DATABASE
        $db->table('tb_member_vip')
            ->where('id_hfm', $id_hfm)
            ->delete();

        session()->setFlashdata('pesan', 'Data berhasil dihapus & member otomatis di-kick dari WA.');
        return redirect()->to(base_url('AdminDashboard'));
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
