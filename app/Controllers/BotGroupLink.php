<?php

namespace App\Controllers;

use App\Models\BotGroupLinkModel;

class BotGroupLink extends BaseController
{
    private BotGroupLinkModel $model;

    public function __construct()
    {
        $this->model = new BotGroupLinkModel();
    }

    public function index()
    {
        return view('bot_group_links/index', [
            'title' => 'Link Grup Telegram & WhatsApp',
            'links' => $this->model->orderBy('platform', 'ASC')->orderBy('id', 'ASC')->findAll(),
        ]);
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
