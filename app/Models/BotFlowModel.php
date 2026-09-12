<?php

namespace App\Models;

use CodeIgniter\Model;

class BotFlowModel extends Model
{
    protected $table            = 'bot_flows';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['step_level', 'step_name', 'trigger_keywords', 'reply_message', 'fallback_message', 'fallback_video_url', 'fallback_image_url'];
}
