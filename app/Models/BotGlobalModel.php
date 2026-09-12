<?php

namespace App\Models;

use CodeIgniter\Model;

class BotGlobalModel extends Model
{
    protected $table            = 'bot_globals';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['key_name', 'key_value'];
}
