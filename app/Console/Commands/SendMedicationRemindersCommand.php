<?php

namespace App\Console\Commands;

use App\Services\Medications\MedicationReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendMedicationRemindersCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'medications:send-reminders {--sync : Send reminders synchronously instead of queuing}';

    /**
     * @var string
     */
    protected $description = 'Create, send, and miss-mark Health Assist medication reminders';

    public function handle(MedicationReminderService $service): int
    {
        $result = $service->process(
            CarbonImmutable::now('UTC'),
            (bool) $this->option('sync'),
        );

        $this->info(sprintf(
            'Medication reminders: created=%d sent=%d missed=%d',
            $result['created'],
            $result['sent'],
            $result['missed'],
        ));

        return self::SUCCESS;
    }
}
