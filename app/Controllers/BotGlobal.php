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
            'globals' => $this->globalModel->findAll()
        ];
        return view('bot_global/index', $data);
    }

    public function create()
    {
        return view('bot_global/create', ['title' => 'Tambah Variabel Global']);
    }

    public function store()
    {
        $this->globalModel->save([
            'key_name'  => $this->request->getPost('key_name'),
            'key_value' => $this->request->getPost('key_value')
        ]);
        return redirect()->to('/bot-global')->with('pesan', 'Variabel berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $data = [
            'title'  => 'Edit Variabel Global',
            'global' => $this->globalModel->find($id)
        ];
        return view('bot_global/edit', $data);
    }

    public function update($id)
    {
        $this->globalModel->update($id, [
            'key_name'  => $this->request->getPost('key_name'),
            'key_value' => $this->request->getPost('key_value')
        ]);
        return redirect()->to('/bot-global')->with('pesan', 'Variabel berhasil diubah.');
    }

    public function delete($id)
    {
        $this->globalModel->delete($id);
        return redirect()->to('/bot-global')->with('pesan', 'Variabel berhasil dihapus.');
    }
}
