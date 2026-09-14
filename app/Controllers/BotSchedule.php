<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Database;

class BotSchedule extends BaseController
{
    private const DEFAULTS = [
        ['schedule_key' => 'daily_report', 'schedule_name' => 'Laporan harian database', 'schedule_type' => 'report', 'run_at' => '04:05', 'is_active' => 1],
        ['schedule_key' => 'activity_reminder', 'schedule_name' => 'Evaluasi dan reminder aktivitas', 'schedule_type' => 'activity', 'run_at' => '08:00', 'is_active' => 1],
    ];

    public function index()
    {
        $this->ensureTable();
        return view('bot_schedules/index', [
            'title' => 'Jadwal Pengiriman Bot',
            'schedules' => Database::connect()->table('bot_schedules')->orderBy('id', 'ASC')->get()->getResultArray(),
        ]);
    }

    public function update($id)
    {
        $this->ensureTable();
        $runAt = trim((string) $this->request->getPost('run_at'));
        $active = $this->request->getPost('is_active') ? 1 : 0;
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $runAt)) {
            return redirect()->back()->with('pesan', 'Format jam harus HH:MM.');
        }
        Database::connect()->table('bot_schedules')->where('id', (int) $id)->update([
            'run_at' => $runAt,
            'is_active' => $active,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/bot-schedules')->with('pesan', 'Jadwal berhasil disimpan. Bot akan membaca perubahan otomatis.');
    }

    public function api()
    {
        $this->ensureTable();
        return $this->response->setJSON([
            'status' => 'ok',
            'timezone' => 'Asia/Jakarta',
            'schedules' => Database::connect()->table('bot_schedules')->where('is_active', 1)->get()->getResultArray(),
        ]);
    }

    public function restartBot()
    {
        // Jangan bergantung pada port 3000: jika bot mati, endpoint Node juga mati.
        // Dashboard menjalankan PM2 langsung pada proses yang sudah ditentukan.
        if (!function_exists('exec')) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Fungsi exec() PHP dinonaktifkan di server. Hapus exec dari disable_functions lalu restart Apache.',
            ]);
        }
        $service = 'hfm-bot-restart.service';
        if (!is_file('/etc/systemd/system/' . $service)) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Service restart bot belum terpasang di VPS.',
                'detail' => 'Pasang deploy/hfm-bot-restart.service ke /etc/systemd/system/.',
            ]);
        }
        $command = '/usr/bin/sudo -n -u bonichi /usr/bin/pm2 restart bot_tele_hfm --update-env 2>&1';
        $output = [];
        $exitCode = 1;
        exec($command, $output, $exitCode);
        $detail = sprintf(
            'exit_code=%d; euid=%s; output=%s',
            $exitCode,
            (string) (function_exists('posix_geteuid') ? posix_geteuid() : 'unknown'),
            implode(' | ', $output) ?: 'kosong'
        );
        @file_put_contents('/tmp/hfm-bot-restart.log', date('c') . ' ' . $detail . PHP_EOL, FILE_APPEND);

        if ($exitCode !== 0) {
            log_message('error', 'Gagal restart bot_tele_hfm: ' . $detail);
            $error = 'Gagal restart bot_tele_hfm. ' . $detail;
            return $this->response->setStatusCode(500)->setJSON([
                'error' => $error,
                'detail' => $detail,
            ]);
        }

        return $this->response->setJSON(['message' => 'bot_tele_hfm berhasil direstart.', 'output' => implode(' ', $output)]);
    }

    public function sendReport()
    {
        $tokenRow = Database::connect()->table('bot_globals')
            ->select('key_value')
            ->where('key_name', 'BOT_CONTROL_TOKEN')
            ->get()->getRowArray();
        $token = (string) ($tokenRow['key_value'] ?? '');
        if ($token === '') {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'BOT_CONTROL_TOKEN belum dikonfigurasi.']);
        }

        $ch = curl_init('http://127.0.0.1:3000/api/send-daily-report');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['token' => $token]),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 90,
        ]);
        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($raw ?: '', true);
        if ($curlError || $httpCode < 200 || $httpCode >= 300) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'Gagal mengirim report manual dari bot.', 'detail' => $curlError ?: ($data['error'] ?? 'HTTP ' . $httpCode)]);
        }
        return $this->response->setJSON($data ?: ['message' => 'Report manual berhasil dikirim.']);
    }

    private function ensureTable(): void
    {
        $db = Database::connect();
        $db->query("CREATE TABLE IF NOT EXISTS `bot_schedules` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `schedule_key` VARCHAR(50) NOT NULL,
            `schedule_name` VARCHAR(150) NOT NULL,
            `schedule_type` VARCHAR(30) NOT NULL,
            `run_at` CHAR(5) NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`), UNIQUE KEY `uq_bot_schedule_key` (`schedule_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        foreach (self::DEFAULTS as $row) {
            $exists = $db->table('bot_schedules')->where('schedule_key', $row['schedule_key'])->countAllResults();
            if ($exists === 0) $db->table('bot_schedules')->insert($row);
        }
    }
}

// Menjalankan ulang proses PM2 dilakukan oleh endpoint Node bot yang terproteksi,
// bukan oleh PHP melalui exec agar akun web tidak mendapatkan akses shell umum.
