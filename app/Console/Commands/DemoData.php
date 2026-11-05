<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\DataReset;
use App\Support\Permissions;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\HistoricalDemoSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;

/**
 * Replaces all operational data with a realistic demo set, for trying the system.
 *
 * Kept: staff accounts (passwords and 2FA untouched), roles and permissions,
 * countries, currencies, services. Everything else is deleted first.
 * Take a database backup before running it.
 */
class DemoData extends Command
{
    protected $signature = 'alraed:demo-data
        {--admin-name= : Name shown for the administrator in the panel}
        {--since= : Generate a realistic history from this date (YYYY-MM-DD) up to now}
        {--confirm= : Type DELETE to skip the confirmation question}';

    protected $description = 'Delete all transfers, customers, requests and announcements, then load demo data';

    public function handle(): int
    {
        $admin = User::query()->role(Permissions::ROLE_ADMIN)->where('is_active', true)->orderBy('id')->first();
        if (! $admin) {
            $this->error('No active administrator found. Run: php artisan alraed:create-user --role=admin');

            return self::FAILURE;
        }

        $this->warn('This will permanently delete:');
        foreach (DataReset::counts() as $table => $rows) {
            $this->line(sprintf('  %-22s %d rows', $table, $rows));
        }
        $this->line('  + uploaded receipt and delivery-proof files');
        $this->line('Kept: staff accounts, passwords, 2FA, roles, countries, currencies, services.');

        $confirmed = $this->option('confirm') === 'DELETE'
            || $this->ask('Type DELETE to continue') === 'DELETE';
        if (! $confirmed) {
            $this->info('Cancelled. Nothing was changed.');

            return self::SUCCESS;
        }

        $name = trim((string) ($this->option('admin-name') ?? $this->ask('Name to show for the administrator in the panel', $admin->name)));
        if ($name !== '') {
            $admin->forceFill(['name' => $name])->save();
        }

        DataReset::wipe();
        $this->callSilently('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
        $this->callSilently('db:seed', ['--class' => ReferenceDataSeeder::class, '--force' => true]);

        // Generated rows are not staff actions, so they stay out of the audit log.
        $summary = AuditLog::muted(function () use ($admin) {
            if ($since = $this->option('since')) {
                $this->info('Generating history month by month (this can take a few minutes)…');

                return (new HistoricalDemoSeeder)->runHistory(
                    $admin,
                    CarbonImmutable::parse($since),
                    fn (string $month) => $this->line('  ✓ ' . $month),
                );
            }

            return (new DemoDataSeeder)->runFor($admin);
        });

        $this->newLine();
        $this->info('Demo data loaded:');
        foreach ($summary as $label => $count) {
            $this->line(sprintf('  %-28s %s', $label, $count));
        }
        $this->newLine();
        $this->line('Demo staff accounts are created INACTIVE with unknown passwords.');
        $this->line('To try a role: panel → النظام → المستخدمون → edit → set a password and activate.');

        return self::SUCCESS;
    }
}
