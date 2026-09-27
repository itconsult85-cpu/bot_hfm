<?php

namespace App\Controllers;

use App\Models\BotGlobalModel;

class BotGlobal extends BaseController
{
    protected $globalModel;

    public function __construct()
    {
        $this->globalModel = new BotGlobalModel();
    }

    public function index()
    {
        $data = [
            'title'   => 'Pengaturan Variabel Global Bot',
            // ID_GRUP_VIP dipelihara melalui CRUD Link Grup agar tidak ada input ganda.
            'globals' => $this->globalModel->where('key_name !=', 'ID_GRUP_VIP')->findAll()
        ];
        return view('bot_global/index', $data);
    }

    public function create()
    {
        return view('bot_global/create', ['title' => 'Tambah Variabel Global']);
    }

    public function store()
    {
        if (strtoupper(trim((string) $this->request->getPost('key_name'))) === 'ID_GRUP_VIP') {
            return redirect()->to('/bot-group-links')->with('error', 'ID grup Telegram sekarang dikelola dari menu Link Grup Tele & WhatsApp.');
        }

        $this->globalModel->save([
            'key_name'  => $this->request->getPost('key_name'),
            'key_value' => $this->request->getPost('key_value')
        ]);
        return redirect()->to('/bot-global')->with('pesan', 'Variabel berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $global = $this->globalModel->find($id);
        if (($global['key_name'] ?? '') === 'ID_GRUP_VIP') {
            return redirect()->to('/bot-group-links')->with('error', 'ID grup Telegram sekarang dikelola dari menu Link Grup Tele & WhatsApp.');
        }

        $data = [
            'title'  => 'Edit Variabel Global Bot',
            'global' => $global
        ];
        return view('bot_global/edit', $data);
    }

    public function update($id)
    {
        $global = $this->globalModel->find($id);
        if (($global['key_name'] ?? '') === 'ID_GRUP_VIP' || strtoupper(trim((string) $this->request->getPost('key_name'))) === 'ID_GRUP_VIP') {
            return redirect()->to('/bot-group-links')->with('error', 'ID grup Telegram sekarang dikelola dari menu Link Grup Tele & WhatsApp.');
        }

        $this->globalModel->update($id, [
            'key_name'  => $this->request->getPost('key_name'),
            'key_value' => $this->request->getPost('key_value')
        ]);
        return redirect()->to('/bot-global')->with('pesan', 'Variabel berhasil diubah.');
    }

    public function delete($id)
    {
        $global = $this->globalModel->find($id);
        if (($global['key_name'] ?? '') === 'ID_GRUP_VIP') {
            return redirect()->to('/bot-group-links')->with('error', 'ID grup Telegram sekarang dikelola dari menu Link Grup Tele & WhatsApp.');
        }

        $this->globalModel->delete($id);
        return redirect()->to('/bot-global')->with('pesan', 'Variabel berhasil dihapus.');
    }
}
