<?php

namespace App\Controllers;

use App\Models\BotFaqModel;

class BotFaq extends BaseController
{
    protected $faqModel;

    public function __construct()
    {
        $this->faqModel = new BotFaqModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Manajemen FAQ Bot',
            'faqs'  => $this->faqModel->findAll()
        ];
        return view('bot_faq/index', $data);
    }

    public function create()
    {
        $data = ['title' => 'Tambah FAQ Baru'];
        return view('bot_faq/create', $data);
    }

    public function store()
    {
        $this->faqModel->save([
            'keywords'      => $this->request->getPost('keywords'),
            'reply_message' => $this->request->getPost('reply_message'),
            'action_type'   => $this->request->getPost('action_type')
        ]);
        return redirect()->to('/bot-faq')->with('success', 'Data FAQ berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit FAQ',
            'faq'   => $this->faqModel->find($id)
        ];
        return view('bot_faq/edit', $data);
    }

    public function update($id)
    {
        $this->faqModel->update($id, [
            'keywords'      => $this->request->getPost('keywords'),
            'reply_message' => $this->request->getPost('reply_message'),
            'action_type'   => $this->request->getPost('action_type')
        ]);
        return redirect()->to('/bot-faq')->with('success', 'Data FAQ berhasil diubah.');
    }

    public function delete($id)
    {
        $this->faqModel->delete($id);
        return redirect()->to('/bot-faq')->with('success', 'Data FAQ berhasil dihapus.');
    }
}
