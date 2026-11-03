<?php

namespace App\Filament\Resources\Transfers\Pages;

use App\Enums\TransferStatus;
use App\Filament\Resources\Transfers\TransferResource;
use App\Models\Transfer;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTransfers extends ListRecords
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        $open = [TransferStatus::New, TransferStatus::PendingPricing, TransferStatus::PendingCustomerApproval, TransferStatus::PendingTransfer,
                 TransferStatus::PendingVerification, TransferStatus::UnderReview, TransferStatus::Approved, TransferStatus::InProgress, TransferStatus::OnHold];

        return [
            'all' => Tab::make(__('fields.tab_all')),
            'open' => Tab::make(__('fields.tab_open'))->badge(Transfer::whereIn('status', $open)->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', $open)),
            'delivered' => Tab::make(__('fields.tab_delivered'))
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', TransferStatus::Delivered)),
            'closed' => Tab::make(__('fields.tab_closed'))
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', [TransferStatus::Cancelled, TransferStatus::Rejected])),
        ];
    }
}
