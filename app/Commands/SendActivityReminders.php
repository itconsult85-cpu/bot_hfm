<?php

namespace App\Commands;

use App\Libraries\ActivityReminderService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendActivityReminders extends BaseCommand
{
    protected $group = 'Bot';
    protected $name = 'bot:send-activity-reminders';
    protected $description = 'Kirim pengingat aktivitas member yang jatuh tempo.';

    public function run(array $params)
    {
        $phase = $params[0] ?? null;
        if ($phase !== null && !in_array($phase, ['h3', 'h1', 'final'], true)) {
            CLI::error('Phase harus h3, h1, atau final.');
            return EXIT_ERROR;
        }
        $result = (new ActivityReminderService())->sendDue($phase);
        CLI::write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $result['failed'] > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }
}

