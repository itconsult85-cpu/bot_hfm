<?php

namespace App\Controllers;

use App\Models\ClientModel;

class Client extends BaseController
{
    public function index()
    {
        $model = new ClientModel();
        $data['pasif'] = $model->getInactiveClients();

        return view('admin_followup', $data);
    }

    public function prosesFollowUp()
    {
        $model = new ClientModel();
        $clients = $model->getInactiveClients();
        $berhasil = 0;

        foreach ($clients as $c) {
            $pesan = "Halo Kak {$c['nama']}! 👋 Ini Cuanchi dari HFM. Udah lama nih kita nggak ngobrol. Kalau ada yang bisa Cuanchi bantu soal trading, langsung chat aja ya Kak! 😊";

            $ch = curl_init('http://localhost:3000/kirim-pesan');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'nomor' => $c['no_wa'],
                'pesan' => $pesan
            ]));
            curl_exec($ch);
            curl_close($ch);

            $berhasil++;
        }

        return $this->response->setJSON(['status' => 'success', 'dikirim' => $berhasil]);
    }

    public function apiLaporanHarian()
    {
        $db = \Config\Database::connect();

        // Mengambil rentang waktu hari ini full
        $hariIni = date('Y-m-d');
        $kemarin = date('Y-m-d', strtotime('-1 day'));

        // Rentang Waktu: 04:00 Pagi KEMARIN sampai 03:59 Pagi HARI INI
        $startWaktu = $kemarin . ' 04:00:00';
        $endWaktu   = $hariIni . ' 03:59:59';

        // 1. REKAP MEMBER MASUK (Berdasarkan ID Telegram Unik)
        $masukQuery = $db->table('tb_member_logs')
            ->select('COUNT(DISTINCT tb_member_vip.id_telegram) as total')
            ->join('tb_member_vip', 'tb_member_vip.id_hfm = tb_member_logs.id_hfm OR tb_member_vip.no_wa = tb_member_logs.no_wa', 'left')
            ->where('tb_member_vip.id_telegram !=', '')
            ->where('tb_member_vip.id_telegram !=', '-')
            ->where('tb_member_vip.id_telegram IS NOT NULL')
            ->groupStart()
            ->whereIn('tb_member_logs.tipe_aktivitas', ['masuk', 'masuk_id_baru', 'aktif_kembali', 'masuk_via_form'])
            ->groupEnd()
            ->where('tb_member_logs.created_at >=', $startWaktu)
            ->where('tb_member_logs.created_at <=', $endWaktu)
            ->get()->getRowArray();
        $masuk = $masukQuery['total'] ?? 0;

        // 2. REKAP KELUAR SENDIRI (Berdasarkan ID Telegram Unik)
        $keluarQuery = $db->table('tb_member_logs')
            ->select('COUNT(DISTINCT tb_member_vip.id_telegram) as total')
            ->join('tb_member_vip', 'tb_member_vip.id_hfm = tb_member_logs.id_hfm OR tb_member_vip.no_wa = tb_member_logs.no_wa', 'left')
            ->where('tb_member_vip.id_telegram !=', '')
            ->where('tb_member_vip.id_telegram !=', '-')
            ->where('tb_member_vip.id_telegram IS NOT NULL')
            ->where('tb_member_logs.tipe_aktivitas', 'keluar_sendiri')
            ->where('tb_member_logs.created_at >=', $startWaktu)
            ->where('tb_member_logs.created_at <=', $endWaktu)
            ->get()->getRowArray();
        $keluar_sendiri = $keluarQuery['total'] ?? 0;

        // 3. REKAP LEPAS IB / KICK ADMIN (Berdasarkan ID Telegram Unik)
        $lepasQuery = $db->table('tb_member_logs')
            ->select('COUNT(DISTINCT tb_member_vip.id_telegram) as total')
            ->join('tb_member_vip', 'tb_member_vip.id_hfm = tb_member_logs.id_hfm OR tb_member_vip.no_wa = tb_member_logs.no_wa', 'left')
            ->where('tb_member_vip.id_telegram !=', '')
            ->where('tb_member_vip.id_telegram !=', '-')
            ->where('tb_member_vip.id_telegram IS NOT NULL')
            ->groupStart()
            ->whereIn('tb_member_logs.tipe_aktivitas', ['lepas_ib', 'keluar_di_remove', 'keluar_grup'])
            ->groupEnd()
            ->where('tb_member_logs.created_at >=', $startWaktu)
            ->where('tb_member_logs.created_at <=', $endWaktu)
            ->get()->getRowArray();
        $lepas_ib = $lepasQuery['total'] ?? 0;

        return $this->response->setJSON([
            'status' => 'ok',
            'data' => [
                'masuk' => $masuk,
                'keluar_sendiri' => $keluar_sendiri,
                'lepas_ib' => $lepas_ib,
                'debug_waktu' => "Data ditarik dari $startWaktu sampai $endWaktu"
            ]
        ]);
    }

    public function apiGetPasif()
    {
        set_time_limit(0);

        $db = \Config\Database::connect();
        $builder = $db->table('tb_member_vip')->where('status', 'aktif');

        $dataPasif = [];
        $api_key = "127e07f2-3b2a-4cb5-9a5b-0610e4ecc86e";
        $batasHariPasif = 30;
        $sekarang = new \DateTime(date('Y-m-d'));

        $builder->chunk(100, function ($members_chunk) use ($sekarang, $batasHariPasif, $api_key, &$dataPasif) {
            foreach ($members_chunk as $m) {
                $m = (array) $m;
                $id_hfm = $m['id_hfm'];
                $url = "https://api.hfm-partners.com/api/clients/" . $id_hfm . "/report";

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $api_key,
                    'Accept: application/json'
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                $response = curl_exec($ch);
                curl_close($ch);

                if ($response) {
                    $data = json_decode($response, true);
                    if (isset($data['id']) && $data['id'] != null) {
                        $tanggal_patokan = null;

                        if (!empty($data['last_trade'])) {
                            $tanggal_patokan = $data['last_trade'];
                        } elseif (!empty($data['first_funding'])) {
                            $tanggal_patokan = $data['first_funding'];
                        } elseif (!empty($data['registration'])) {
                            $tanggal_patokan = $data['registration'];
                        }

                        if ($tanggal_patokan) {
                            $tgl_bersih = substr($tanggal_patokan, 0, 10);
                            $tgl_terakhir = new \DateTime($tgl_bersih);
                            $selisih = $sekarang->diff($tgl_terakhir)->days;

                            if ($selisih >= $batasHariPasif) {
                                $dataPasif[] = [
                                    'no_wa' => $m['no_wa'],
                                    'nama'  => $m['nama'] ?? 'Kak',
                                    'hari_pasif' => $selisih
                                ];
                            }
                        }
                    }
                }
            }
        });

        return $this->response->setJSON([
            'status' => 'ok',
            'total'  => count($dataPasif),
            'data'   => $dataPasif
        ]);
    }

    public function check_status_vip($no_wa)
    {
        $db = \Config\Database::connect();

        // Pengecekan via Telegram ID atau Nomor WA
        $user = $db->table('tb_member_vip')
            ->groupStart()
            ->where('no_wa', $no_wa)
            ->orWhere('id_telegram', $no_wa)
            ->groupEnd()
            ->where('status', 'aktif')
            ->get()->getRowArray();

        if ($user) {
            return $this->response->setJSON(['is_vip' => true]);
        }
        return $this->response->setJSON(['is_vip' => false]);
    }

    public function cekHfm($id_hfm, $no_wa = null)
    {
        if ($no_wa === null) {
            return $this->response->setJSON(['status' => 'error', 'pesan' => 'Nomor WA tidak terbaca.']);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('tb_member_vip');
        $cek_db = $builder->where('id_hfm', $id_hfm)->get()->getRowArray();

        if ($cek_db) {
            $db_wa = $cek_db['no_wa'];
            if ($db_wa == $no_wa) {
                return $this->response->setJSON(['status' => 'valid_sempurna', 'pesan' => 'ID sudah terdaftar atas nama Kakak.']);
            } else {
                $awal = substr($db_wa, 0, 4);
                $akhir = substr($db_wa, -4);
                $sensor_wa = $awal . "xxxx" . $akhir;
                return $this->response->setJSON([
                    'status' => 'id_diklaim_orang_lain',
                    'no_tersensor' => $sensor_wa
                ]);
            }
        }

        $api_key = "127e07f2-3b2a-4cb5-9a5b-0610e4ecc86e";
        $url = "https://api.hfm-partners.com/api/clients/" . $id_hfm . "/report";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || !$response) {
            return $this->response->setJSON(['status' => 'error', 'pesan' => 'Gagal terhubung ke API HFM. Error: ' . $err]);
        }

        $rawData = json_decode($response, true);
        $data = [];

        if (isset($rawData[0]) && is_array($rawData[0])) {
            $data = $rawData[0];
        } elseif (isset($rawData['data']) && is_array($rawData['data'])) {
            $data = $rawData['data'];
        } else {
            $data = $rawData;
        }

        if (!isset($data['id']) || empty($data['id']) || $data['id'] == 0) {
            return $this->response->setJSON([
                'status' => 'invalid',
                'pesan'  => "❌ Maaf kak, ID Trading {$id_hfm} tidak ditemukan atau belum terdaftar di bawah IB BOSSCUAN.\n\nPastikan angka ID-nya benar, atau jika baru pindah IB, mohon tunggu beberapa saat ya!"
            ]);
        }

        $currency = $data['account_currency'] ?? 'USD';
        $deposit_murni = (float) ($data['deposits'] ?? 0);
        $balance       = (float) ($data['balance'] ?? 0);
        $equity        = (float) ($data['equity'] ?? 0);
        $deposit = max($deposit_murni, $balance, $equity);
        $nama = $data['name'] ?? 'Member BOSSCUAN';

        $last_trade = null;
        $raw_last_trade = $data['last_trade'] ?? 'N/A';
        if (empty($raw_last_trade) || $raw_last_trade === 'N/A' || !preg_match('/^20\d{2}/', $raw_last_trade)) {
            $last_trade = '0000-00-00 00:00:00';
        } else {
            $last_trade = date('Y-m-d H:i:s', strtotime($raw_last_trade));
        }

        $lulus = false;
        $min = "";

        if ($currency === 'IDR') {
            if ($deposit >= 100000) $lulus = true;
            $min = "Rp 100.000";
        } elseif ($currency === 'USC') {
            if ($deposit >= 550) $lulus = true;
            $min = "550 USC";
        } else {
            if ($deposit >= 10) $lulus = true;
            $min = "$10";
        }

        if ($lulus) {
            // 🛑 GATEKEEPER: JIKA DARI BOT TELEGRAM, HANYA CEK VALIDITAS (JANGAN SIMPAN)
            if ($no_wa === "ADMIN_CHECK" || $no_wa === "CHECK_ONLY") {
                return $this->response->setJSON(['status' => 'valid_sempurna']);
            }

            $memberLepasIb = $db->table('tb_member_vip')
                ->where('no_wa', $no_wa)
                ->where('status', 'lepas_ib')
                ->orderBy('updated_at', 'ASC')
                ->get()
                ->getRowArray();

            if ($memberLepasIb) {
                $db->table('tb_member_vip')->where('id', $memberLepasIb['id'])->update([
                    'id_hfm'     => $id_hfm,
                    'status'     => 'aktif',
                    'nama'       => $nama,
                    'deposit'    => $deposit,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $db->table('tb_member_logs')->insert([
                    'no_wa'          => $no_wa,
                    'nama'           => $nama,
                    'id_hfm'         => $id_hfm,
                    'tipe_aktivitas' => 'aktif_kembali'
                ]);
            } else {
                $db->table('tb_member_vip')->insert([
                    'id_hfm'     => $id_hfm,
                    'no_wa'      => $no_wa,
                    'nama'       => $nama,
                    'deposit'    => $deposit,
                    'currency'   => $currency,
                    'status'     => 'aktif',
                    'last_trade' => $last_trade,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $db->table('tb_member_logs')->insert([
                    'no_wa'          => $no_wa,
                    'nama'           => $nama,
                    'id_hfm'         => $id_hfm,
                    'tipe_aktivitas' => 'masuk_id_baru'
                ]);
            }
            return $this->response->setJSON(['status' => 'valid_sempurna']);
        } else {
            return $this->response->setJSON([
                'status' => 'kurang_deposit',
                'pesan'  => "Saldo/Deposit Kakak saat ini $deposit $currency.\nUntuk bisa bergabung di Grup VIP silakan isi saldo minimal {$min}."
            ]);
        }
    }

    public function simpanMemberForm()
    {
        $json = $this->request->getJSON();

        $id_hfm = $json->id_hfm ?? '';
        $no_wa = $json->no_wa ?? '';
        $nama_form = $json->nama ?? ''; // Nama dari inputan form user
        $email = $json->email ?? '';
        $id_telegram = $json->id_telegram ?? '';

        if (!$id_hfm || !$no_wa || !$nama_form) {
            return $this->response->setJSON(['status' => 'error', 'pesan' => 'Data formulir tidak lengkap.']);
        }

        $db = \Config\Database::connect();

        // 1. CEK DUPLIKASI ID TRADING
        $cek_db = $db->table('tb_member_vip')->where('id_hfm', $id_hfm)->get()->getRowArray();
        if ($cek_db) {
            return $this->response->setJSON(['status' => 'error', 'pesan' => 'ID Trading ini sudah terdaftar di sistem.']);
        }

        // 2. TARIK DATA OTOMATIS DARI API HFM
        $api_key = "127e07f2-3b2a-4cb5-9a5b-0610e4ecc86e";
        $url = "https://api.hfm-partners.com/api/clients/" . $id_hfm . "/report";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        curl_close($ch);

        // Siapkan nilai default jika API tiba-tiba gangguan
        $deposit_real = 0;
        $currency_real = 'USD';
        $last_trade_real = '0000-00-00 00:00:00';
        $nama_real = $nama_form;

        // 3. PROSES JSON DARI HFM
        if ($response) {
            $dataApi = json_decode($response, true);

            // Mengekstrak data dari format objek tunggal HFM
            if (isset($dataApi['id'])) {

                // Kalkulasi deposit: Ambil nilai tertinggi antara deposits, balance, atau equity
                $depo   = (float)($dataApi['deposits'] ?? 0);
                $bal    = (float)($dataApi['balance'] ?? 0);
                $equity = (float)($dataApi['equity'] ?? 0);
                $deposit_real = max($depo, $bal, $equity);

                // Ambil mata uang (USD, USC, IDR)
                $currency_real = $dataApi['account_currency'] ?? 'USD';

                // Utamakan nama asli yang terdaftar di HFM
                if (!empty($dataApi['name'])) {
                    $nama_real = $dataApi['name'];
                }

                // Ambil waktu transaksi terakhir (potong milidetik agar formatnya rapi untuk MySQL)
                $raw_last_trade = $dataApi['last_trade'] ?? 'N/A';
                if (!empty($raw_last_trade) && $raw_last_trade !== 'N/A') {
                    $last_trade_real = date('Y-m-d H:i:s', strtotime(substr($raw_last_trade, 0, 19)));
                }
            }
        }

        // 4. SIMPAN DATA KE DATABASE DENGAN DATA ASLI API
        $db->table('tb_member_vip')->insert([
            'id_hfm'      => $id_hfm,
            'no_wa'       => $no_wa,
            'nama'        => $nama_real,
            'email'       => $email,
            'id_telegram' => $id_telegram,
            'deposit'     => $deposit_real,
            'currency'    => $currency_real,
            'status'      => 'aktif',
            'last_trade'  => $last_trade_real,
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        $db->table('tb_member_logs')->insert([
            'no_wa'          => $no_wa,
            'nama'           => $nama_real,
            'id_hfm'         => $id_hfm,
            'tipe_aktivitas' => 'masuk_via_form'
        ]);

        return $this->response->setJSON(['status' => 'sukses']);
    }

    public function apiMemberKeluarIB()
    {
        set_time_limit(0);
        $db = \Config\Database::connect();
        $dataKick = [];
        $api_key = "127e07f2-3b2a-4cb5-9a5b-0610e4ecc86e";
        $members = $db->table('tb_member_vip')->where('status', 'aktif')->get()->getResultArray();

        foreach ($members as $m) {
            $id_hfm = $m['id_hfm'];
            $url = "https://api.hfm-partners.com/api/clients/" . $id_hfm . "/report";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $api_key, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if (!$err && $response) {
                $data = json_decode($response, true);
                if (!isset($data['id']) || $data['id'] == null) {
                    $db->table('tb_member_logs')->insert(['no_wa' => $m['no_wa'], 'nama' => $m['nama'], 'tipe_aktivitas' => 'lepas_ib']);
                    $dataKick[] = ['no_wa' => $m['no_wa'], 'nama'  => $m['nama'] ?? 'Kak'];
                    $db->table('tb_member_vip')->where('id_hfm', $id_hfm)->update(['status' => 'lepas_ib']);
                }
            }
        }
        return $this->response->setJSON(['status' => 'ok', 'total'  => count($dataKick), 'data'   => $dataKick]);
    }

    // ==========================================
    // API UNTUK MENGHAPUS MEMBER KELUAR GRUP WA
    // ==========================================
    // public function apiHapusMemberGrup($no_wa)
    // {
    //     $db = \Config\Database::connect();

    //     // MENGHAPUS DATA SECARA PERMANEN DARI DATABASE
    //     $db->table('tb_member_vip')->where('no_wa', $no_wa)->delete();

    //     return $this->response->setJSON([
    //         'status' => 'sukses',
    //         'pesan'  => "Data nomor $no_wa berhasil dihapus karena keluar dari grup."
    //     ]);
    // }

    public function apiHapusMemberGrup1($no_wa)
    {
        // Tambahkan log untuk debugging di file writable/logs/
        log_message('debug', 'Mencoba menghapus nomor: ' . $no_wa);

        $db = \Config\Database::connect();
        $builder = $db->table('tb_member_vip');

        // Pastikan kolom 'no_wa' di database adalah tipe VARCHAR/TEXT 
        // dan tidak memiliki spasi tersembunyi
        $deleted = $builder->where('no_wa', $no_wa)->delete();

        if ($deleted) {
            return $this->response->setJSON([
                'status' => 'sukses',
                'pesan'  => "Data nomor $no_wa berhasil dihapus"
            ]);
        } else {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'gagal',
                'pesan'  => "Data tidak ditemukan atau gagal dihapus"
            ]);
        }
    }

    public function apiHapusMemberGrup2($no_wa)
    {
        // Bersihkan nomor dari karakter non-digit
        $no_wa = preg_replace('/[^0-9]/', '', $no_wa);

        $db = \Config\Database::connect();

        // DEBUG: Ambil 1 sampel data dari database untuk melihat format aslinya
        $sample = $db->table('tb_member_vip')->get()->getFirstRow();
        log_message('debug', 'DEBUG FORMAT DB: ' . ($sample ? $sample->no_wa : 'kosong'));
        log_message('debug', 'DEBUG MENCARI: ' . $no_wa);

        // Gunakan query manual agar kita bisa melihat hasilnya
        $builder = $db->table('tb_member_vip');

        // Kita cari dengan LIKE yang fleksibel (ambil 8 digit terakhir saja untuk menghindari prefix 0/62)
        $deleted = $builder->like('no_wa', substr($no_wa, -8))->delete();

        $jumlah_terhapus = $db->affectedRows();
        log_message('debug', 'JUMLAH TERHAPUS: ' . $jumlah_terhapus);

        if ($jumlah_terhapus > 0) {
            return $this->response->setJSON(['status' => 'sukses', 'pesan' => "Terhapus $jumlah_terhapus"]);
        } else {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'gagal', 'pesan' => "Data $no_wa tidak ditemukan"]);
        }
    }

    // public function apiHapusMemberGrup($no_wa)
    // {
    //     // Bersihkan nomor dari karakter non-digit
    //     $no_wa = preg_replace('/[^0-9]/', '', $no_wa);

    //     $db = \Config\Database::connect();

    //     $member = $db->table('tb_member_vip')->where('no_wa', $no_wa)->get()->getRowArray();

    //     if ($member) {
    //         $db->table('tb_member_logs')->insert([
    //             'no_wa' => $member['no_wa'],
    //             'nama' => $member['nama'],
    //             'tipe_aktivitas' => 'keluar_sendiri'
    //         ]);

    //         $db->table('tb_member_vip')->where('no_wa', $no_wa)->delete();

    //         return $this->response->setJSON(['status' => 'sukses', 'pesan' => 'Data dicatat ke log & dihapus']);
    //     }

    //     return $this->response->setStatusCode(404)->setJSON(['status' => 'gagal', 'pesan' => 'Data tidak ditemukan']);
    // }

    public function apiUpdateStatusMemberGrup($no_wa, $jenis_keluar = 'keluar_sendiri')
    {
        $no_wa = preg_replace('/[^0-9]/', '', $no_wa);
        $db = \Config\Database::connect();

        // Cari dari nomor WA atau Telegram ID
        $member = $db->table('tb_member_vip')
            ->groupStart()->where('no_wa', $no_wa)->orWhere('id_telegram', $no_wa)->groupEnd()
            ->get()->getRowArray();

        if ($member) {
            $db->table('tb_member_logs')->insert([
                'no_wa'          => $member['no_wa'],
                'nama'           => $member['nama'],
                'id_hfm'         => $member['id_hfm'],
                'tipe_aktivitas' => $jenis_keluar
            ]);
            $db->table('tb_member_vip')->where('id', $member['id'])->update([
                'status'     => 'keluar_grup',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            return $this->response->setJSON(['status' => 'sukses', 'pesan' => 'Status diubah menjadi keluar_grup & dicatat ke log']);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'gagal', 'pesan' => 'Data tidak ditemukan']);
    }

    // =========================================================
    // API SEMENTARA: UNTUK MENGUPDATE DATA LAST TRADE YANG KOSONG
    // =========================================================
    public function updateSemuaLastTrade()
    {
        // Menghindari timeout jika data di database sudah sangat banyak
        set_time_limit(0);

        $db = \Config\Database::connect();

        // Ambil member yang last_trade-nya NULL, atau 1970, atau 0000
        $members = $db->table('tb_member_vip')
            ->groupStart()
            ->where('last_trade IS NULL')
            ->orWhere('last_trade', '1970-01-01 00:00:00')
            ->orWhere('last_trade', '0000-00-00 00:00:00')
            ->groupEnd()
            ->get()
            ->getResultArray();

        $api_key = "127e07f2-3b2a-4cb5-9a5b-0610e4ecc86e";
        $berhasil_diupdate = 0;

        foreach ($members as $m) {
            $id_hfm = $m['id_hfm'];

            $url = "https://api.hfm-partners.com/api/clients/" . $id_hfm . "/report";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $api_key,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if (!$err && $response) {
                $data = json_decode($response, true);

                // ==========================================
                // LOGIKA YANG BENAR ANTI 1970
                // ==========================================
                $raw_last_trade = $data['last_trade'] ?? 'N/A';

                if (empty($raw_last_trade) || $raw_last_trade === 'N/A' || !preg_match('/^20\d{2}/', $raw_last_trade)) {
                    $last_trade_update = '0000-00-00 00:00:00';
                } else {
                    $last_trade_update = date('Y-m-d H:i:s', strtotime($raw_last_trade));
                }

                $db->table('tb_member_vip')
                    ->where('id_hfm', $id_hfm)
                    ->update(['last_trade' => $last_trade_update]);

                $berhasil_diupdate++;
            }
        }

        return $this->response->setJSON([
            'status' => 'sukses',
            'pesan'  => "Berhasil mengecek " . count($members) . " data bermasalah. Total $berhasil_diupdate akun telah diupdate jadi format yang benar."
        ]);
    }

    // =========================================================
    // API SEMENTARA: SINKRONISASI MATA UANG MEMBER LAMA
    // =========================================================
    public function updateSemuaCurrency()
    {
        // Menghindari timeout karena memproses banyak data
        set_time_limit(0);

        $db = \Config\Database::connect();

        // Ambil semua data member yang ada di tabel
        $members = $db->table('tb_member_vip')->get()->getResultArray();

        $api_key = "127e07f2-3b2a-4cb5-9a5b-0610e4ecc86e";
        $berhasil_diupdate = 0;

        foreach ($members as $m) {
            $id_hfm = $m['id_hfm'];

            $url = "https://api.hfm-partners.com/api/clients/" . $id_hfm . "/report";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $api_key,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if (!$err && $response) {
                $data = json_decode($response, true);

                // Pastikan HFM mengirimkan data account_currency
                if (isset($data['account_currency']) && !empty($data['account_currency'])) {
                    $currency_asli = $data['account_currency'];

                    // Update ke database
                    $db->table('tb_member_vip')
                        ->where('id_hfm', $id_hfm)
                        ->update(['currency' => $currency_asli]);

                    $berhasil_diupdate++;
                }
            }
        }

        return $this->response->setJSON([
            'status' => 'sukses',
            'pesan'  => "Berhasil mengecek " . count($members) . " member. Total $berhasil_diupdate akun telah diperbarui mata uangnya sesuai API HFM."
        ]);
    }

    // =========================================================
    // API SEMENTARA: SINKRONISASI NAMA MEMBER LAMA DARI HFM
    // =========================================================
    public function updateSemuaNama()
    {
        // Menghindari timeout karena memproses banyak request API
        set_time_limit(0);

        $db = \Config\Database::connect();

        // Ambil data member yang namanya masih kosong, NULL, atau 'Member BOSSCUAN'
        $members = $db->table('tb_member_vip')
            ->groupStart()
            ->where('nama IS NULL')
            ->orWhere('nama', '')
            ->orWhere('nama', 'Member BOSSCUAN')
            ->groupEnd()
            ->get()
            ->getResultArray();

        if (empty($members)) {
            return $this->response->setJSON([
                'status' => 'info',
                'pesan'  => 'Semua data member sudah memiliki nama. Tidak ada yang perlu diupdate.'
            ]);
        }

        $api_key = "127e07f2-3b2a-4cb5-9a5b-0610e4ecc86e";
        $berhasil_diupdate = 0;

        foreach ($members as $m) {
            $id_hfm = $m['id_hfm'];

            $url = "https://api.hfm-partners.com/api/clients/" . $id_hfm . "/report";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $api_key,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if (!$err && $response) {
                $data = json_decode($response, true);

                // Pastikan HFM mengirimkan field name
                if (isset($data['name']) && !empty($data['name'])) {
                    $nama_asli = $data['name'];

                    // Update ke database
                    $db->table('tb_member_vip')
                        ->where('id_hfm', $id_hfm)
                        ->update(['nama' => $nama_asli]);

                    $berhasil_diupdate++;
                }
            }
        }

        return $this->response->setJSON([
            'status' => 'sukses',
            'pesan'  => "Berhasil memproses " . count($members) . " member yang belum ada namanya. Total $berhasil_diupdate nama akun telah berhasil disimpan."
        ]);
    }

    // ==========================================
    // API BARU: PENGINGAT MEMBER PASIF 30 HARI
    // ==========================================
    public function apiReminderPasif()
    {
        $db = \Config\Database::connect();

        // Ambil tanggal tepat 30 hari yang lalu (Format YYYY-MM-DD)
        $tgl_target = date('Y-m-d', strtotime('-30 days'));

        // Query: 
        // 1. Yang pernah trade, dan last_trade persis 30 hari lalu
        // 2. Yang BELUM pernah trade (0000-00-00), dan created_at persis 30 hari lalu
        $sql = "SELECT * FROM tb_member_vip 
                WHERE status = 'aktif' 
                AND (
                    (last_trade != '0000-00-00 00:00:00' AND DATE(last_trade) = ?)
                    OR 
                    (last_trade = '0000-00-00 00:00:00' AND DATE(created_at) = ?)
                )";

        $members = $db->query($sql, [$tgl_target, $tgl_target])->getResultArray();

        $dataPesan = [];

        foreach ($members as $m) {
            // Tentukan template pesan berdasarkan status trading mereka
            if ($m['last_trade'] === '0000-00-00 00:00:00') {
                $pesan = "Halo kak aku Cuanci, aku perhatiin udah 30 hari nih sejak kakak daftar VIP tapi belum sempat nyobain trading. Ada kendala kah saat depo atau pakai MT5-nya? Sini aku bantu kak 😊";
            } else {
                $pesan = "Halo kak aku Cuanci, udah 30 hari nih kakak absen trading. Market bulan ini lagi asik banget lho pergerakannya! Kalau ada analisa yang membingungkan, yuk diskusi di grup VIP. Gas lagi kak! 🚀";
            }

            $dataPesan[] = [
                'no_wa' => $m['no_wa'],
                'pesan' => $pesan
            ];
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'total'  => count($dataPesan),
            'data'   => $dataPesan
        ]);
    }
}
