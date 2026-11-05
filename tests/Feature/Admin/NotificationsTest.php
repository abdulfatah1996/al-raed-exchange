<?php

namespace Tests\Feature\Admin;

use App\Enums\TransferStatus;
use App\Models\TransferRequest;
use App\Models\User;
use App\Support\Permissions;
use Tests\Feature\AlraedTestCase;

class NotificationsTest extends AlraedTestCase
{
    private function inbox(User $user): array
    {
        return $user->fresh()->notifications->map(fn ($n) => $n->data['title'] ?? '')->all();
    }

    public function test_website_request_notifies_staff_who_handle_requests(): void
    {
        $employee = $this->staff(Permissions::ROLE_EMPLOYEE, ['locale' => 'ar']);
        $supervisor = $this->staff(Permissions::ROLE_SUPERVISOR, ['locale' => 'en']);
        $cashier = $this->staff(Permissions::ROLE_CASHIER);
        $inactive = $this->staff(Permissions::ROLE_EMPLOYEE, ['is_active' => false]);

        $request = TransferRequest::create(['name' => 'Sara', 'phone' => '+970598000111', 'operation' => 'withdraw', 'amount' => 700]);

        $this->assertSame(["طلب جديد من الموقع {$request->reference}"], $this->inbox($employee));
        $this->assertSame(["New website request {$request->reference}"], $this->inbox($supervisor));
        $this->assertSame([], $this->inbox($cashier));
        $this->assertSame([], $this->inbox($inactive));
    }

    public function test_html_in_a_public_request_name_is_shown_as_text_in_staff_notifications(): void
    {
        $employee = $this->staff(Permissions::ROLE_EMPLOYEE, ['locale' => 'en']);

        TransferRequest::create([
            'name' => '<a href="https://evil.example/login">Click to verify</a><img src=x>',
            'phone' => '+970598000111', 'operation' => 'withdraw', 'amount' => 700,
        ]);

        $data = $employee->fresh()->notifications->first()->data;
        $html = \Filament\Notifications\Notification::fromArray($data)->toEmbeddedHtml();

        $this->assertStringNotContainsString('<a href="https://evil.example', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('Click to verify', $html);
    }

    public function test_notifications_are_written_immediately_without_a_queue_worker(): void
    {
        config(['queue.default' => 'database']);
        $employee = $this->staff(Permissions::ROLE_EMPLOYEE);

        TransferRequest::create(['name' => 'Sara', 'phone' => '+970598000111', 'operation' => 'withdraw', 'amount' => 700]);

        $this->assertCount(1, $employee->fresh()->notifications);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_workflow_notifies_the_next_role_and_never_the_actor(): void
    {
        $supervisor = $this->staff(Permissions::ROLE_SUPERVISOR);
        $cashier = $this->staff(Permissions::ROLE_CASHIER);
        $transfer = $this->transfer(TransferStatus::PendingTransfer);

        $this->actingAs($supervisor);
        $transfer->transitionTo(TransferStatus::PendingVerification);
        $this->assertSame([], $this->inbox($supervisor), 'actor must not notify themselves');

        $this->actingAs($this->staff(Permissions::ROLE_EMPLOYEE));
        $this->forceStatus($transfer, TransferStatus::PendingTransfer)->transitionTo(TransferStatus::PendingVerification);
        $this->assertCount(1, $this->inbox($supervisor));
        $this->assertSame([], $this->inbox($cashier));

        $this->forceStatus($transfer, TransferStatus::UnderReview)->transitionTo(TransferStatus::Approved);
        $this->assertCount(1, $this->inbox($cashier));
        $this->assertStringContainsString($transfer->reference, $this->inbox($cashier)[0]);
    }

    public function test_assignee_is_notified(): void
    {
        $assignee = $this->staff(Permissions::ROLE_EMPLOYEE);
        $this->actingAs($this->staff(Permissions::ROLE_SUPERVISOR));

        $transfer = $this->transfer();
        $transfer->update(['assigned_user_id' => $assignee->id]);

        $this->assertCount(1, $this->inbox($assignee));
        $this->assertSame(
            route('filament.alraed.resources.transfers.view', $transfer),
            $assignee->fresh()->notifications->first()->data['actions'][0]['url'],
        );
    }
}
