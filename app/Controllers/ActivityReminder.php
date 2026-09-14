<?php

namespace App\Controllers;

use App\Libraries\ActivityReminderService;
use Config\Database;
class ActivityReminder extends BaseController
{
    private ActivityReminderService $service;

    public function __construct()
    {
        $this->service = new ActivityReminderService();
    }

    public function index()
    {
        return view('activity_reminders/index', [
            'title' => 'Evaluasi Aktivitas Member',
            'members' => $this->service->eligibleMembers(),
        ]);
    }

    public function process()
    {
        $result = $this->service->sendDue();
        return redirect()->to('/activity-reminders')->with('pesan', sprintf(
            'Proses selesai: %d terkirim, %d sudah pernah terkirim, %d gagal.',
            $result['sent'], $result['skipped'], $result['failed']
        ));
    }

    // Dipanggil scheduler bot pada pukul 08:00 WIB.
    public function runScheduled()
    {
        $result = $this->service->sendDue();

        return $this->response->setJSON([
            'status' => $result['failed'] > 0 ? 'partial' : 'ok',
            'result' => $result,
        ]);
    }

    // Hanya dijalankan setelah admin menekan tombol hapus.
    public function remove($id)
    {
        $db = Database::connect();
        $member = $db->table('tb_member_vip')->where('id', (int) $id)->get()->getRowArray();
        if (!$member) {
            return redirect()->to('/activity-reminders')->with('pesan', 'Member tidak ditemukan.');
        }
        if (!empty($member['id_telegram']) && $member['id_telegram'] !== '-') {
            $this->postBot('/kick-telegram', ['id_telegram' => $member['id_telegram']]);
        }
        $db->table('tb_member_logs')->insert([
            'no_wa' => $member['no_wa'],
            'id_hfm' => $member['id_hfm'],
            'nama' => $member['nama'],
            'tipe_aktivitas' => 'keluar_di_remove',
        ]);
        $db->table('tb_member_vip')->where('id', (int) $id)->delete();
        return redirect()->to('/activity-reminders')->with('pesan', 'Member berhasil di-kick dan dihapus oleh admin.');
    }

    private function postBot(string $path, array $payload): void
    {
        $ch = curl_init('http://127.0.0.1:3000' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 5,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

}
