<?php

namespace App\Filament\Resources\Transfers\Pages;

use App\Filament\Resources\Transfers\Concerns\ChangesStatus;
use App\Filament\Resources\Transfers\TransferResource;
use Filament\Resources\Pages\EditRecord;

class EditTransfer extends EditRecord
{
    use ChangesStatus;

    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->changeStatusAction()];
    }
}
