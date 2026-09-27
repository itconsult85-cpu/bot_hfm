<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsappGroupMemberModel extends Model
{
    protected $table = 'whatsapp_group_members';
    protected $primaryKey = 'id';
    protected $allowedFields = ['whatsapp_id', 'phone_number', 'display_name', 'group_id', 'status', 'joined_at', 'left_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
