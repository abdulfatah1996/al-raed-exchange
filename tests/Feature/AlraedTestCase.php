<?php

namespace Tests\Feature;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Customer;
use App\Models\Receipt;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class AlraedTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function staff(string $role, array $attributes = []): User
    {
        return User::factory()->create($attributes)->assignRole($role);
    }

    /** Creates a transfer and puts it straight into $status (bypassing the workflow, test setup only). */
    protected function transfer(TransferStatus $status = TransferStatus::New, array $attributes = []): Transfer
    {
        $customer = Customer::create(['name' => 'Test Customer', 'phone' => '+970590000000']);
        $transfer = Transfer::create(['customer_id' => $customer->id, 'type' => TransferType::Receive, ...$attributes]);

        return $this->forceStatus($transfer, $status);
    }

    protected function forceStatus(Transfer $transfer, TransferStatus $status): Transfer
    {
        DB::table('transfers')->where('id', $transfer->id)->update(['status' => $status->value]);

        return $transfer->refresh();
    }

    protected function receipt(Transfer $transfer, array $attributes = []): Receipt
    {
        return Receipt::create(['transfer_id' => $transfer->id, 'amount' => '100.00', ...$attributes])->refresh();
    }

    /** A transfer in progress with one verified + delivered receipt and a delivery proof. */
    protected function readyToDeliver(): Transfer
    {
        $transfer = $this->transfer(TransferStatus::PendingVerification);
        $receipt = $this->receipt($transfer, ['external_ref' => 'TX-READY-' . $transfer->id]);
        $receipt->verify();
        $this->forceStatus($transfer, TransferStatus::InProgress);
        $receipt->markDelivered();
        DB::table('transfers')->where('id', $transfer->id)->update(['delivery_proof_file' => 'proofs/p.jpg']);

        return $transfer->refresh();
    }
}
