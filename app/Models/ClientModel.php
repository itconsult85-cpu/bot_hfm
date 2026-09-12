<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientModel extends Model
{
    protected $table = 'clients';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_hfm', 'no_wa', 'nama', 'created_at', 'status'];

    public function getInactiveClients()
    {
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        return $this->where('last_active <', $thirtyDaysAgo)->findAll();
    }
}
