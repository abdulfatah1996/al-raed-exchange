<?php

namespace App\Filament\Resources\Transfers\Pages;

use App\Filament\Resources\Transfers\Concerns\ChangesStatus;
use App\Filament\Resources\Transfers\TransferResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/** Read-only page — the only way to open delivered or cancelled transfers. */
class ViewTransfer extends ViewRecord
{
    use ChangesStatus;

    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->changeStatusAction(), EditAction::make()];
    }
}
