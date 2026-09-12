<?php

namespace App\Models;

use CodeIgniter\Model;

class UserProgressModel extends Model
{
    protected $table            = 'user_progress';
    protected $primaryKey       = 'user_id';

    protected $useAutoIncrement = false;

    protected $returnType       = 'array';

    protected $allowedFields    = [
        'user_id',
        'user_name',
        'phone_number',
        'current_step',
        'screenshots_sent',
        'last_active',
        'started_at',
        'completed_at'
    ];
}
