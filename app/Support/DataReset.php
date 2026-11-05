<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/** Deletes operational data. Shared by alraed:clear-data and alraed:demo-data. */
final class DataReset
{
    /** Operational tables, children first. */
    public const TABLES = [
        'audit_logs', 'notifications', 'transfer_requests', 'receipts', 'transfer_status_logs', 'transfers',
        'customer_notes', 'customers', 'payment_accounts', 'announcements',
    ];

    /** Accounts created by the demo command. */
    public const DEMO_STAFF_EMAILS = 'demo-%@alraed.local';

    /** @return array<string, int> table => rows */
    public static function counts(): array
    {
        return collect(self::TABLES)->filter(fn ($t) => Schema::hasTable($t))
            ->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
    }

    public static function demoStaffCount(): int
    {
        return User::where('email', 'like', self::DEMO_STAFF_EMAILS)->count();
    }

    public static function wipe(bool $removeDemoStaff = false): void
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

        if ($removeDemoStaff) {
            \App\Models\AuditLog::muted(fn () => User::where('email', 'like', self::DEMO_STAFF_EMAILS)->get()->each(function (User $user) {
                $user->syncRoles([]);
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->delete();
            }));
        }
    }
}
