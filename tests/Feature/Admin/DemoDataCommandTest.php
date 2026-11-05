<?php

namespace Tests\Feature\Admin;

use App\Enums\TransferStatus;
use App\Models\Customer;
use App\Models\Transfer;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\AlraedTestCase;

class DemoDataCommandTest extends AlraedTestCase
{
    public function test_nothing_is_deleted_without_typing_delete(): void
    {
        $this->staff(Permissions::ROLE_ADMIN);
        $this->seed(ReferenceDataSeeder::class);
        Customer::create(['name' => 'Real customer', 'phone' => '+970599999999']);

        $this->artisan('alraed:demo-data')->expectsQuestion('Type DELETE to continue', 'no')->assertSuccessful();

        $this->assertSame(['Real customer'], Customer::pluck('name')->all());
    }

    public function test_replaces_operational_data_and_keeps_staff_accounts(): void
    {
        Storage::fake('local');
        $admin = $this->staff(Permissions::ROLE_ADMIN, ['password' => 'Kept-pass-2026']);
        $this->seed(ReferenceDataSeeder::class);
        Customer::create(['name' => 'Old customer', 'phone' => '+970599999999']);

        $this->artisan('alraed:demo-data', ['--confirm' => 'DELETE', '--admin-name' => 'عبد الفتاح عبيد'])->assertSuccessful();

        $admin->refresh();
        $this->assertSame('عبد الفتاح عبيد', $admin->name);
        $this->assertTrue(Hash::check('Kept-pass-2026', $admin->password));
        $this->assertFalse(Customer::where('name', 'Old customer')->exists());

        $statuses = Transfer::pluck('status')->map->value->unique()->sort()->values()->all();
        foreach (TransferStatus::cases() as $status) {
            $this->assertContains($status->value, $statuses, "missing a demo transfer in {$status->value}");
        }

        $this->assertSame(4, User::where('email', 'like', 'demo-%@alraed.local')->where('is_active', false)->count());
        $this->assertGreaterThan(0, $admin->notifications()->count());
        $this->assertNotEmpty(Storage::disk('local')->files('receipts'));

        // Every delivered transfer satisfies the delivery rule it went through.
        Transfer::where('status', TransferStatus::Delivered)->get()->each(fn (Transfer $t) => $this->assertNotNull($t->delivery_proof_file));
    }

    public function test_clear_data_empties_the_system_but_keeps_real_staff_and_settings(): void
    {
        Storage::fake('local');
        $admin = $this->staff(Permissions::ROLE_ADMIN);
        $employee = $this->staff(Permissions::ROLE_EMPLOYEE);
        $this->seed(ReferenceDataSeeder::class);
        $this->artisan('alraed:demo-data', ['--confirm' => 'DELETE', '--admin-name' => 'Admin'])->assertSuccessful();

        $this->artisan('alraed:clear-data')->expectsQuestion('Type DELETE to continue', 'DELETE')->assertSuccessful();

        foreach (\App\Support\DataReset::TABLES as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame(0, User::where('email', 'like', 'demo-%@alraed.local')->count());
        $this->assertTrue($admin->fresh()->hasRole('admin'));
        $this->assertNotNull($employee->fresh());
        $this->assertGreaterThan(0, \App\Models\Currency::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_clear_data_cancels_without_delete(): void
    {
        Customer::create(['name' => 'Kept', 'phone' => '+970599999999']);

        $this->artisan('alraed:clear-data')->expectsQuestion('Type DELETE to continue', 'نعم')->assertSuccessful();

        $this->assertSame(1, Customer::count());
    }

    public function test_history_is_consistent_and_never_in_the_future(): void
    {
        Storage::fake('local');
        $this->staff(Permissions::ROLE_ADMIN);
        $this->seed(ReferenceDataSeeder::class);

        $this->artisan('alraed:demo-data', ['--confirm' => 'DELETE', '--admin-name' => 'Admin', '--since' => now()->subDays(45)->toDateString()])->assertSuccessful();

        $this->assertGreaterThan(60, Transfer::count());
        $this->assertSame(0, \App\Models\TransferStatusLog::where('created_at', '>', now())->count());
        $this->assertSame(0, Transfer::where('created_at', '<', now()->subDays(46))->count());

        // Each step was performed by the role that owns it.
        $approvers = \App\Models\TransferStatusLog::where('to_status', TransferStatus::Approved->value)->with('user')->get()->pluck('user')->unique('id');
        $this->assertTrue($approvers->every(fn ($u) => $u->hasRole(Permissions::ROLE_SUPERVISOR)));
        $deliverers = \App\Models\TransferStatusLog::where('to_status', TransferStatus::Delivered->value)->with('user')->get()->pluck('user')->unique('id');
        $this->assertTrue($deliverers->every(fn ($u) => $u->hasRole(Permissions::ROLE_CASHIER)));

        Transfer::where('status', TransferStatus::Delivered)->get()->each(function (Transfer $t) {
            $this->assertTrue($t->receipts()->get()->every(fn ($r) => $r->delivery_status === \App\Enums\ReceiptDelivery::Delivered));
            $this->assertNotNull($t->delivery_proof_file);
        });
    }
}
