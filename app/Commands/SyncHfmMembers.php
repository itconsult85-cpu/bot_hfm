<?php

namespace App\Commands;

use App\Libraries\ActivityReminderService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncHfmMembers extends BaseCommand
{
    protected $group = 'HFM';
    protected $name = 'hfm:sync-members';
    protected $description = 'Sinkronisasi last_trade, nama, dan currency dari API HFM.';

    public function run(array $params)
    {
        $result = (new ActivityReminderService())->syncAllHfm();
        CLI::write(json_encode($result, JSON_PRETTY_PRINT));
        return $result['failed'] > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
