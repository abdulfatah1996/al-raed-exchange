<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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
        {--confirm= : Type DELETE to skip the confirmation question}';

    protected $description = 'Delete all transfers, customers, requests and announcements, then load demo data';

    /** Operational tables, children first. */
    private const TABLES = [
        'notifications', 'transfer_requests', 'receipts', 'transfer_status_logs', 'transfers',
        'customer_notes', 'customers', 'payment_accounts', 'announcements',
    ];

    public function handle(): int
    {
        $admin = User::query()->role(Permissions::ROLE_ADMIN)->where('is_active', true)->orderBy('id')->first();
        if (! $admin) {
            $this->error('No active administrator found. Run: php artisan alraed:create-user --role=admin');

            return self::FAILURE;
        }

        $this->warn('This will permanently delete:');
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table)) {
                $this->line(sprintf('  %-22s %d rows', $table, DB::table($table)->count()));
            }
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

        $this->wipe();
        $this->callSilently('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
        $this->callSilently('db:seed', ['--class' => ReferenceDataSeeder::class, '--force' => true]);

        $summary = (new DemoDataSeeder)->runFor($admin);

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

    private function wipe(): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            foreach (self::TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        foreach (['receipts', 'proofs'] as $dir) {
            Storage::disk('local')->deleteDirectory($dir);
        }
    }
}
