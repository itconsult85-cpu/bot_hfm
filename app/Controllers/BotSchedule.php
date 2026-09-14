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
        $db = Database::connect();
        $row = $db->table('bot_globals')->where('key_name', 'BOT_CONTROL_TOKEN')->get()->getRowArray();
        $token = trim((string) ($row['key_value'] ?? ''));
        if ($token === '') {
            return $this->response->setStatusCode(503)->setJSON(['error' => 'BOT_CONTROL_TOKEN belum diatur di bot_globals.']);
        }

        $ch = curl_init('http://127.0.0.1:3000/api/restart-bot');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['token' => $token]),
            CURLOPT_TIMEOUT => 10,
        ]);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) return $this->response->setStatusCode(502)->setJSON(['error' => 'Bot tidak merespons: ' . $error]);
        $decoded = json_decode($raw, true) ?: ['message' => $raw];
        return $this->response->setStatusCode($status >= 200 && $status < 300 ? 200 : 502)->setJSON($decoded);
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
