<?php

namespace App\Console\Commands;

use App\Jobs\SendAppointmentReminderJob;
use App\Models\AppointmentReminder;
use Illuminate\Console\Command;

class SendAppointmentRemindersCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'appointments:send-reminders {--sync : Send reminders synchronously instead of queuing}';

    /**
     * @var string
     */
    protected $description = 'Dispatch or send due Health Assist appointment reminders';

    public function handle(): int
    {
        $due = AppointmentReminder::query()
            ->withoutGlobalScopes()
            ->where('status', AppointmentReminder::STATUS_PENDING)
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit(200)
            ->get();

        if ($due->isEmpty()) {
            $this->info('No due appointment reminders.');

            return self::SUCCESS;
        }

        foreach ($due as $reminder) {
            if ($this->option('sync')) {
                SendAppointmentReminderJob::dispatchSync($reminder->id);
            } else {
                SendAppointmentReminderJob::dispatch($reminder->id);
            }
        }

        $this->info('Processed '.$due->count().' appointment reminder(s).');

        return self::SUCCESS;
    }
}
