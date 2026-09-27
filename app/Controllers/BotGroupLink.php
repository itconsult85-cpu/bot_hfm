<?php

namespace App\Controllers;

use App\Models\BotGroupLinkModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class BotGroupLink extends BaseController
{
    private BotGroupLinkModel $model;

    public function __construct()
    {
        $this->model = new BotGroupLinkModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();
        $adminRow = $db->table('bot_globals')->where('key_name', 'ID_ADMIN')->get()->getRowArray();

        try {
            $links = $this->model->orderBy('platform', 'ASC')->orderBy('id', 'ASC')->findAll();
        } catch (DatabaseException $e) {
            if ((int) $e->getCode() !== 1146) {
                throw $e;
            }

            return view('bot_group_links/index', [
                'title' => 'Link Grup Telegram & WhatsApp',
                'links' => [],
                'adminId' => $adminRow['key_value'] ?? '',
                'setupError' => 'Tabel bot_group_links belum tersedia. Jalankan migration CodeIgniter dengan perintah: php spark migrate',
            ]);
        }

        return view('bot_group_links/index', [
            'title' => 'Link Grup Telegram & WhatsApp',
            'links' => $links,
            'adminId' => $adminRow['key_value'] ?? '',
        ]);
    }

    public function updateAdminId()
    {
        $adminId = trim((string) $this->request->getPost('admin_id'));
        if ($adminId === '' || !preg_match('/^\d{5,20}$/', $adminId)) {
            return redirect()->to('/bot-group-links')->with('error', 'ID Admin Telegram harus berupa angka 5 sampai 20 digit.');
        }

        $db = \Config\Database::connect();
        $row = $db->table('bot_globals')->where('key_name', 'ID_ADMIN')->get()->getRowArray();
        if ($row) {
            $db->table('bot_globals')->where('id', $row['id'])->update(['key_value' => $adminId]);
        } else {
            $db->table('bot_globals')->insert(['key_name' => 'ID_ADMIN', 'key_value' => $adminId]);
        }

        return redirect()->to('/bot-group-links')->with('pesan', 'ID Admin Telegram berhasil diperbarui.');
    }

    public function create()
    {
        return view('bot_group_links/form', [
            'title' => 'Tambah Link Grup',
            'link' => null,
            'action' => base_url('bot-group-links/store'),
        ]);
    }

    public function store()
    {
        return $this->saveLink();
    }

    public function edit($id)
    {
        $link = $this->model->find((int) $id);
        if (!$link) {
            return redirect()->to('/bot-group-links')->with('pesan', 'Link grup tidak ditemukan.');
        }

        return view('bot_group_links/form', [
            'title' => 'Edit Link Grup',
            'link' => $link,
            'action' => base_url('bot-group-links/update/' . (int) $id),
        ]);
    }

    public function update($id)
    {
        return $this->saveLink((int) $id);
    }

    public function sendWhatsAppButton($id)
    {
        $link = $this->model->where('id', (int) $id)->where('platform', 'whatsapp')->where('is_active', 1)->first();
        $telegram = $this->model->where('platform', 'telegram')->where('is_active', 1)->first();
        if (!$link || !$telegram) {
            return redirect()->to('/bot-group-links')->with('error', 'Link WhatsApp dan konfigurasi Telegram aktif wajib tersedia.');
        }

        $ch = curl_init('http://127.0.0.1:3000/send-whatsapp-button');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['source_link_id' => (int) $id, 'telegram_group_id' => $telegram['group_id']]),
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($raw ?: '', true);
        if ($error || $status < 200 || $status >= 300 || ($data['status'] ?? '') !== 'success') {
            return redirect()->to('/bot-group-links')->with('error', 'Gagal mengirim tombol ke grup Telegram: ' . ($error ?: ($data['error'] ?? 'bot tidak merespons.')));
        }
        return redirect()->to('/bot-group-links')->with('pesan', 'Tombol link WhatsApp berhasil dikirim ke grup Telegram.');
    }

    public function delete($id)
    {
        if (!$this->model->find((int) $id)) {
            return redirect()->to('/bot-group-links')->with('pesan', 'Link grup tidak ditemukan.');
        }

        $this->model->delete((int) $id);
        return redirect()->to('/bot-group-links')->with('pesan', 'Link grup berhasil dihapus.');
    }

    private function saveLink(?int $id = null)
    {
        $platform = strtolower(trim((string) $this->request->getPost('platform')));
        $groupName = trim((string) $this->request->getPost('group_name'));
        $inviteLink = trim((string) $this->request->getPost('invite_link'));
        $groupId = trim((string) $this->request->getPost('group_id'));
        $isActive = $this->request->getPost('is_active') ? 1 : 0;

        $errors = [];
        if (!in_array($platform, ['telegram', 'whatsapp'], true)) {
            $errors[] = 'Platform harus Telegram atau WhatsApp.';
        }
        if ($groupName === '' || strlen($groupName) > 150) {
            $errors[] = 'Nama grup wajib diisi dan maksimal 150 karakter.';
        }
        if (!filter_var($inviteLink, FILTER_VALIDATE_URL) || !preg_match('/^https:\/\//i', $inviteLink)) {
            $errors[] = 'Link grup harus berupa URL HTTPS yang valid.';
        }
        if ($platform === 'telegram' && $groupId === '') {
            $errors[] = 'ID grup Telegram wajib diisi agar bot dapat melakukan kick.';
        }
        if ($platform === 'whatsapp' && $groupId === '') {
            $errors[] = 'ID grup WhatsApp wajib diisi agar bot dapat melakukan kick dan sinkronisasi.';
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $duplicate = $this->model->where('platform', $platform)->where('id !=', $id ?? 0)->first();
        if ($duplicate) {
            return redirect()->back()->withInput()->with('error', 'Sudah ada link aktif untuk platform ' . ucfirst($platform) . '. Edit data yang sudah ada.');
        }

        $data = [
            'platform' => $platform,
            'group_name' => $groupName,
            'invite_link' => $inviteLink,
            'group_id' => $groupId,
            'is_active' => $isActive,
        ];

        if ($id === null) {
            $this->model->insert($data);
            $message = 'Link grup berhasil ditambahkan.';
        } else {
            $this->model->update($id, $data);
            $message = 'Link grup berhasil diperbarui.';
        }

        return redirect()->to('/bot-group-links')->with('pesan', $message);
    }
}
