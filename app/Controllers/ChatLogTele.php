<?php

namespace App\Controllers;

use App\Models\ChatLogTeleModel;

class ChatLogTele extends BaseController
{
    protected $logModel;

    public function __construct()
    {
        $this->logModel = new ChatLogTeleModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Log Obrolan Telegram',
            'users' => $this->logModel->getUniqueUsers()
        ];
        return view('chat_logs/index', $data);
    }

    public function getDetailChat($phone)
    {
        $chats = $this->logModel->getDetailChat($phone);
        return $this->response->setJSON($chats);
    }

    public function clearAll()
    {
        $this->logModel->emptyTable('chat_logs_tele');
        return redirect()->to('/chat-logs')->with('pesan', 'Seluruh riwayat obrolan telah dibersihkan.');
    }
}
