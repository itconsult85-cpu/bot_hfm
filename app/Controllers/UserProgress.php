<?php

namespace App\Controllers;

use App\Models\UserProgressModel;

class UserProgress extends BaseController
{
    protected UserProgressModel $progressModel;

    public function __construct()
    {
        $this->progressModel = new UserProgressModel();
    }

    public function index()
    {
        $search = trim((string) $this->request->getGet('search'));
        $stepRaw = trim((string) $this->request->getGet('step'));
        $step = ctype_digit($stepRaw) && (int) $stepRaw >= 1 && (int) $stepRaw <= 7
            ? (int) $stepRaw
            : null;

        if ($search !== '') {
            $this->progressModel->groupStart()
                ->like('user_name', $search)
                ->orLike('user_id', $search)
                ->orLike('phone_number', $search)
                ->groupEnd();
        }

        if ($step !== null) {
            $this->progressModel->where('current_step', $step);
        }

        $this->progressModel->orderBy('last_active', 'DESC');

        $perPage = 20;
        $progress = $this->progressModel->paginate($perPage);
        $pager = $this->progressModel->pager;

        return view('user_progress/index', [
            'title' => 'Progress Pendaftar Bot',
            'progress' => $progress,
            'pager' => $pager,
            'total' => $pager->getTotal(),
            'currentPage' => $pager->getCurrentPage(),
            'perPage' => $perPage,
            'search' => $search,
            'step_filter' => $step,
        ]);
    }

    public function resetProgress()
    {
        $userId = trim((string) $this->request->getPost('user_id'));
        if (!preg_match('/^\d{5,20}$/D', $userId)) {
            return redirect()->to(base_url('user-progress'))->with('error', 'ID Telegram tidak valid.');
        }

        $row = $this->progressModel->find($userId);
        if (!$row) {
            return redirect()->to(base_url('user-progress'))->with('error', 'Data progres pengguna tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $tokenRow = $db->table('bot_globals')->select('key_value')
            ->where('key_name', 'BOT_CONTROL_TOKEN')->orderBy('id', 'DESC')->get()->getRowArray();
        $token = trim((string) ($tokenRow['key_value'] ?? ''));
        if ($token === '') {
            return redirect()->to(base_url('user-progress'))->with('error', 'BOT_CONTROL_TOKEN belum dikonfigurasi.');
        }

        $ch = curl_init('http://127.0.0.1:3000/reset-user');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Bot-Control-Token: ' . $token,
            ],
            CURLOPT_POSTFIELDS => json_encode(['user_id' => $userId], JSON_UNESCAPED_SLASHES),
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $apiResult = json_decode($response ?: '', true);
        if ($curlError || $httpCode < 200 || $httpCode >= 300 || ($apiResult['status'] ?? null) !== 'success') {
            log_message('error', 'Gagal reset state bot untuk progres user ' . $userId . ': ' . ($curlError ?: 'HTTP ' . $httpCode));
            return redirect()->to(base_url('user-progress'))->with('error', 'State bot belum berhasil direset. Pastikan bot aktif dan BOT_CONTROL_TOKEN bot/CI4 sama.');
        }

        $now = date('Y-m-d H:i:s');
        if (!$this->progressModel->update($userId, [
            'current_step' => 1,
            'screenshots_sent' => 0,
            'started_at' => $now,
            'last_active' => $now,
            'completed_at' => null,
        ])) {
            return redirect()->to(base_url('user-progress'))->with('error', 'State sementara bot sudah dibersihkan, tetapi data progres database gagal diperbarui.');
        }

        return redirect()->to(base_url('user-progress'))->with('success', 'Progres pengguna ' . $userId . ' berhasil direset ke Tahap 1. Status VIP/grup tidak diubah.');
    }
}
