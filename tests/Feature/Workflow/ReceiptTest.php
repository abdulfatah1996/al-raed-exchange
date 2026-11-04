<?php

namespace Tests\Feature\Workflow;

use App\Enums\ReceiptDelivery;
use App\Enums\ReceiptVerification;
use App\Enums\TransferStatus;
use App\Exceptions\InvalidReceiptState;
use App\Models\Receipt;
use App\Support\Permissions;
use Illuminate\Database\QueryException;
use Tests\Feature\AlraedTestCase;

class ReceiptTest extends AlraedTestCase
{
    public function test_several_receipts_are_numbered_per_transfer(): void
    {
        $transfer = $this->transfer(TransferStatus::PendingTransfer);
        $this->receipt($transfer);
        $second = $this->receipt($transfer);

        $this->assertSame(2, $second->number);
    }

    public function test_reference_is_normalised(): void
    {
        $receipt = $this->receipt($this->transfer(), ['external_ref' => '  ab12 cd34 ']);

        $this->assertSame('AB12CD34', $receipt->external_ref);
    }

    public function test_same_txid_cannot_be_used_on_two_transfers_even_if_typed_differently(): void
    {
        $this->receipt($this->transfer(), ['external_ref' => 'abc123']);

        $this->assertTrue(Receipt::refIsTaken(' ABC 123 '));

        $this->expectException(QueryException::class);
        $this->receipt($this->transfer(), ['external_ref' => 'A B C 1 2 3']);
    }

    public function test_deleted_receipt_still_blocks_its_txid(): void
    {
        $receipt = $this->receipt($this->transfer(), ['external_ref' => 'GONE1']);
        $receipt->delete();

        $this->assertTrue(Receipt::refIsTaken('gone1'));
    }

    public function test_cannot_add_receipt_to_approved_transfer(): void
    {
        $this->expectException(InvalidReceiptState::class);
        $this->receipt($this->transfer(TransferStatus::Approved));
    }

    public function test_verification_only_during_checking_stage(): void
    {
        $transfer = $this->transfer(TransferStatus::PendingTransfer);
        $receipt = $this->receipt($transfer);

        try {
            $receipt->verify();
            $this->fail('verified outside the checking stage');
        } catch (InvalidReceiptState) {
        }

        $this->forceStatus($transfer, TransferStatus::PendingVerification);
        $receipt->verify('funds seen on statement');

        $this->assertSame(ReceiptVerification::Verified, $receipt->fresh()->verification_status);
    }

    public function test_verified_receipt_amount_is_frozen(): void
    {
        $receipt = $this->receipt($this->transfer(TransferStatus::PendingVerification));
        $receipt->verify();

        $this->expectException(InvalidReceiptState::class);
        $receipt->fresh()->update(['amount' => '1.00']);
    }

    public function test_rejected_receipt_cannot_be_verified_later(): void
    {
        $receipt = $this->receipt($this->transfer(TransferStatus::PendingVerification));
        $receipt->reject('no funds');

        $this->expectException(InvalidReceiptState::class);
        $receipt->verify();
    }

    public function test_payout_requires_approved_transfer(): void
    {
        $transfer = $this->transfer(TransferStatus::PendingVerification);
        $receipt = $this->receipt($transfer);
        $receipt->verify();

        try {
            $receipt->markDelivered();
            $this->fail('paid out before approval');
        } catch (InvalidReceiptState) {
        }

        $this->forceStatus($transfer, TransferStatus::Approved);
        $receipt->markDelivered();
        $receipt->markDelivered(); // second click is a no-op, not a second payout

        $this->assertSame(ReceiptDelivery::Delivered, $receipt->fresh()->delivery_status);
    }

    public function test_employee_records_but_cannot_verify(): void
    {
        $receipt = $this->receipt($this->transfer(TransferStatus::PendingVerification));

        $this->assertTrue($this->staff(Permissions::ROLE_EMPLOYEE)->can('update', $receipt));
        $this->assertFalse($this->staff(Permissions::ROLE_EMPLOYEE)->can('verify', $receipt));
        $this->assertTrue($this->staff(Permissions::ROLE_SUPERVISOR)->can('verify', $receipt));
    }
}
