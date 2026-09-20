<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class MarkOverdueInvoicesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'billing:mark-overdue';

    /**
     * @var string
     */
    protected $description = 'Mark issued or partially paid Health Assist invoices as overdue past due_at';

    public function handle(): int
    {
        $updated = Invoice::query()
            ->withoutGlobalScopes()
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->update(['status' => Invoice::STATUS_OVERDUE]);

        $this->info("Marked {$updated} invoice(s) overdue.");

        return self::SUCCESS;
    }
}
