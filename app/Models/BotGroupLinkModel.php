<?php

namespace App\Models;

use CodeIgniter\Model;

class BotGroupLinkModel extends Model
{
    protected $table = 'bot_group_links';
    protected $primaryKey = 'id';
    protected $allowedFields = ['platform', 'group_name', 'invite_link', 'group_id', 'is_active'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
