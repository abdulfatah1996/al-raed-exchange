<?php

namespace App\Console\Commands;

use App\Support\DataReset;
use Illuminate\Console\Command;

/**
 * Deletes all operational data (transfers, customers, requests, accounts,
 * announcements, files) and the demo staff accounts, leaving a clean system
 * ready for real work. Real staff accounts, roles, countries, currencies and
 * services are kept so the system stays usable. Take a database backup first.
 */
class ClearData extends Command
{
    protected $signature = 'alraed:clear-data {--confirm= : Type DELETE to skip the confirmation question}';

    protected $description = 'Delete all transfers, customers, requests, accounts, announcements and demo staff';

    public function handle(): int
    {
        $this->warn('This will permanently delete:');
        foreach (DataReset::counts() as $table => $rows) {
            $this->line(sprintf('  %-22s %d rows', $table, $rows));
        }
        $this->line(sprintf('  %-22s %d accounts', 'demo staff', DataReset::demoStaffCount()));
        $this->line('  + uploaded receipt and delivery-proof files');
        $this->line('Kept: your account and other real staff, passwords, 2FA, roles, countries, currencies, services.');

        if ($this->option('confirm') !== 'DELETE' && $this->ask('Type DELETE to continue') !== 'DELETE') {
            $this->info('Cancelled. Nothing was changed.');

            return self::SUCCESS;
        }

        DataReset::wipe(removeDemoStaff: true);

        $this->info('Done. The system is empty and ready for real data.');

        return self::SUCCESS;
    }
}
