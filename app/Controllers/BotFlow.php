<?php

namespace App\Controllers;

use App\Models\BotFlowModel;

class BotFlow extends BaseController
{
    protected $flowModel;

    public function __construct()
    {
        $this->flowModel = new BotFlowModel();
    }

    public function index()
    {
        // Urutkan berdasarkan step_level
        $data = [
            'title' => 'Alur Pendaftaran Bot (Flows)',
            'flows' => $this->flowModel->orderBy('step_level', 'ASC')->findAll()
        ];
        return view('bot_flow/index', $data);
    }

    public function create()
    {
        return view('bot_flow/create', ['title' => 'Tambah Alur Baru']);
    }

    public function store()
    {
        $this->flowModel->save([
            'step_level'         => $this->request->getPost('step_level'),
            'step_name'          => $this->request->getPost('step_name'),
            'trigger_keywords'   => $this->request->getPost('trigger_keywords'),
            'reply_message'      => $this->request->getPost('reply_message'),
            'fallback_message'   => $this->request->getPost('fallback_message'),
            'fallback_video_url' => $this->request->getPost('fallback_video_url')
        ]);
        return redirect()->to('/bot-flow')->with('pesan', 'Alur bot berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit Alur Bot',
            'flow'  => $this->flowModel->find($id)
        ];
        return view('bot_flow/edit', $data);
    }

    public function update($id)
    {
        $this->flowModel->update($id, [
            'step_level'         => $this->request->getPost('step_level'),
            'step_name'          => $this->request->getPost('step_name'),
            'trigger_keywords'   => $this->request->getPost('trigger_keywords'),
            'reply_message'      => $this->request->getPost('reply_message'),
            'fallback_message'   => $this->request->getPost('fallback_message'),
            'fallback_video_url' => $this->request->getPost('fallback_video_url')
        ]);
        return redirect()->to('/bot-flow')->with('pesan', 'Alur bot berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->flowModel->delete($id);
        return redirect()->to('/bot-flow')->with('pesan', 'Alur bot berhasil dihapus.');
    }
}
