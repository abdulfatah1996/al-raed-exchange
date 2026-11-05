<?php

namespace Tests\Feature\Workflow;

use App\Enums\TransferStatus;
use App\Exceptions\InvalidTransferTransition;
use App\Support\Permissions;
use Tests\Feature\AlraedTestCase;

class TransferWorkflowTest extends AlraedTestCase
{
    public function test_every_status_change_is_logged(): void
    {
        $transfer = $this->transfer();
        $transfer->transitionTo(TransferStatus::PendingPricing, 'priced soon');

        $this->assertSame(TransferStatus::PendingPricing, $transfer->status);
        $this->assertDatabaseHas('transfer_status_logs', [
            'transfer_id' => $transfer->id, 'from_status' => 'new', 'to_status' => 'pending_pricing', 'note' => 'priced soon',
        ]);
    }

    public function test_illegal_jump_is_refused(): void
    {
        $this->expectException(InvalidTransferTransition::class);
        $this->transfer()->transitionTo(TransferStatus::Approved);
    }

    public function test_on_hold_can_only_return_to_where_it_was_held_from(): void
    {
        $transfer = $this->transfer();
        $transfer->transitionTo(TransferStatus::PendingPricing);
        $transfer->transitionTo(TransferStatus::OnHold);

        $this->assertEqualsCanonicalizing(
            [TransferStatus::PendingPricing, TransferStatus::Cancelled],
            $transfer->allowedNextStatuses(),
        );

        $this->expectException(InvalidTransferTransition::class);
        $transfer->transitionTo(TransferStatus::Approved); // used to be possible: skipped pricing + verification
    }

    public function test_locked_transfer_rejects_financial_edits(): void
    {
        $transfer = $this->transfer(TransferStatus::Approved, ['send_amount' => '1000']);

        $this->expectException(InvalidTransferTransition::class);
        $transfer->update(['send_amount' => '999']);
    }

    public function test_locked_transfer_still_accepts_notes_and_delivery_proof(): void
    {
        $transfer = $this->transfer(TransferStatus::InProgress);
        $transfer->update(['internal_notes' => 'called customer', 'delivery_proof_file' => 'proofs/x.jpg']);

        $this->assertSame('proofs/x.jpg', $transfer->fresh()->delivery_proof_file);
    }

    public function test_closed_transfer_rejects_proof_replacement(): void
    {
        $transfer = $this->readyToDeliver();
        $transfer->transitionTo(TransferStatus::Delivered);

        $this->expectException(InvalidTransferTransition::class);
        $transfer->update(['delivery_proof_file' => 'proofs/other.jpg']);
    }

    public function test_delivery_requires_proof(): void
    {
        $transfer = $this->readyToDeliver();
        \DB::table('transfers')->where('id', $transfer->id)->update(['delivery_proof_file' => null]);

        $this->expectException(InvalidTransferTransition::class);
        $transfer->refresh()->transitionTo(TransferStatus::Delivered);
    }

    public function test_stale_copy_cannot_deliver_a_second_time(): void
    {
        $transfer = $this->readyToDeliver();
        $first = $transfer->fresh();
        $second = $transfer->fresh(); // another employee's screen, loaded before the first delivery

        $first->transitionTo(TransferStatus::Delivered);

        $this->expectException(InvalidTransferTransition::class);
        $second->transitionTo(TransferStatus::Delivered);
    }

    public function test_only_permitted_roles_may_approve_and_deliver(): void
    {
        $underReview = $this->transfer(TransferStatus::UnderReview);
        $inProgress = $this->readyToDeliver();

        $employee = $this->staff(Permissions::ROLE_EMPLOYEE);
        $cashier = $this->staff(Permissions::ROLE_CASHIER);
        $supervisor = $this->staff(Permissions::ROLE_SUPERVISOR);

        $this->assertFalse($employee->can('transition', [$underReview, TransferStatus::Approved]));
        $this->assertFalse($cashier->can('transition', [$underReview, TransferStatus::Approved]));
        $this->assertTrue($supervisor->can('transition', [$underReview, TransferStatus::Approved]));

        $this->assertFalse($employee->can('transition', [$inProgress, TransferStatus::Delivered]));
        $this->assertTrue($cashier->can('transition', [$inProgress, TransferStatus::Delivered]));
    }

    public function test_delivered_transfer_is_read_only_for_everyone(): void
    {
        $transfer = $this->readyToDeliver();
        $transfer->transitionTo(TransferStatus::Delivered);
        $admin = $this->staff(Permissions::ROLE_ADMIN);

        $this->assertTrue($admin->can('view', $transfer));
        $this->assertFalse($admin->can('update', $transfer));
        $this->assertFalse($admin->can('delete', $transfer));
    }

    public function test_status_history_uses_the_application_clock(): void
    {
        $this->travelTo(now()->subDays(3)->setTime(10, 15));
        $transfer = $this->transfer();
        $this->travel(40)->minutes();
        $transfer->transitionTo(TransferStatus::PendingPricing);

        $times = $transfer->statusLogs()->reorder('id')->pluck('created_at')->map->format('Y-m-d H:i')->all();
        $this->assertSame([now()->subMinutes(40)->format('Y-m-d H:i'), now()->format('Y-m-d H:i')], $times);
    }
}
