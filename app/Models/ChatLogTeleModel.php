<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatLogTeleModel extends Model
{
    protected $table            = 'chat_logs_tele';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['phone_number', 'sender', 'message', 'created_at'];

    public function getUniqueUsers()
    {
        return $this->select('phone_number, MAX(created_at) as last_active')
            ->groupBy('phone_number')
            ->orderBy('last_active', 'DESC')
            ->findAll();
    }

    public function getDetailChat($phone)
    {
        return $this->where('phone_number', $phone)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}
