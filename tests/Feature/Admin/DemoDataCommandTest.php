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
}
