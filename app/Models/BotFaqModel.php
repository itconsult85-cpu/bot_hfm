<?php

namespace App\Models;

use CodeIgniter\Model;

class BotFaqModel extends Model
{
    protected $table            = 'bot_faqs';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['keywords', 'reply_message', 'action_type'];
}
