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
            // ID_GRUP_VIP dan ID_ADMIN dipelihara di CRUD Link Grup agar tidak ada input ganda.
            'globals' => $this->globalModel
                ->whereNotIn('key_name', ['ID_GRUP_VIP', 'ID_ADMIN'])
                ->findAll()
        ];
        return view('bot_global/index', $data);
    }

    public function create()
    {
        return view('bot_global/create', ['title' => 'Tambah Variabel Global']);
    }

    public function store()
    {
        $keyName = strtoupper(trim((string) $this->request->getPost('key_name')));
        if (in_array($keyName, ['ID_GRUP_VIP', 'ID_ADMIN'], true)) {
            return redirect()->to('/bot-group-links')->with('error', 'ID grup dan ID admin Telegram sekarang dikelola dari menu Link Grup Tele & WhatsApp.');
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
        if (in_array(($global['key_name'] ?? ''), ['ID_GRUP_VIP', 'ID_ADMIN'], true)) {
            return redirect()->to('/bot-group-links')->with('error', 'ID grup dan ID admin Telegram sekarang dikelola dari menu Link Grup Tele & WhatsApp.');
        }

        return view('bot_global/edit', [
            'title'  => 'Edit Variabel Global Bot',
            'global' => $global
        ]);
    }

    public function update($id)
    {
        $global = $this->globalModel->find($id);
        $keyName = strtoupper(trim((string) $this->request->getPost('key_name')));
        if (in_array(($global['key_name'] ?? ''), ['ID_GRUP_VIP', 'ID_ADMIN'], true) || in_array($keyName, ['ID_GRUP_VIP', 'ID_ADMIN'], true)) {
            return redirect()->to('/bot-group-links')->with('error', 'ID grup dan ID admin Telegram sekarang dikelola dari menu Link Grup Tele & WhatsApp.');
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
        if (in_array(($global['key_name'] ?? ''), ['ID_GRUP_VIP', 'ID_ADMIN'], true)) {
            return redirect()->to('/bot-group-links')->with('error', 'ID grup dan ID admin Telegram sekarang dikelola dari menu Link Grup Tele & WhatsApp.');
        }

        $this->globalModel->delete($id);
        return redirect()->to('/bot-global')->with('pesan', 'Variabel berhasil dihapus.');
    }
}
