<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $table            = 'tb_admin';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['username', 'password', 'nama_lengkap'];
}