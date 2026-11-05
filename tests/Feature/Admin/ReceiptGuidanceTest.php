<?php

namespace Tests\Feature\Admin;

use App\Enums\TransferStatus;
use App\Filament\Resources\Transfers\Pages\ViewTransfer;
use App\Filament\Resources\Transfers\RelationManagers\ReceiptsRelationManager;
use App\Support\Permissions;
use Livewire\Livewire;
use Tests\Feature\AlraedTestCase;

class ReceiptGuidanceTest extends AlraedTestCase
{
    private function hint($transfer): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(ReceiptsRelationManager::class, ['ownerRecord' => $transfer, 'pageClass' => ViewTransfer::class]);
    }

    public function test_receipts_table_tells_staff_the_next_step(): void
    {
        app()->setLocale('ar');
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN));

        $early = $this->transfer(TransferStatus::PendingTransfer);
        $this->receipt($early);
        $this->hint($early)->assertSee('بانتظار التشييك');

        $checking = $this->transfer(TransferStatus::PendingVerification);
        $this->receipt($checking);
        $this->hint($checking)->assertSee(__('fields.hint.verify'));

        $this->hint($this->readyToDeliver())->assertDontSee(__('fields.hint.verify'));
    }

    public function test_status_can_be_changed_from_the_view_page(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN));
        $transfer = $this->transfer(TransferStatus::PendingTransfer);

        Livewire::test(ViewTransfer::class, ['record' => $transfer->getKey()])
            ->callAction('changeStatus', ['status' => TransferStatus::PendingVerification->value])
            ->assertHasNoActionErrors();

        $this->assertSame(TransferStatus::PendingVerification, $transfer->fresh()->status);
    }
}
