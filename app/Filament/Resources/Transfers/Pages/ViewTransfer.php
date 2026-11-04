<?php

namespace App\Filament\Resources\Transfers\Pages;

use App\Filament\Resources\Transfers\TransferResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/** Read-only page — the only way to open delivered or cancelled transfers. */
class ViewTransfer extends ViewRecord
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
