<?php

namespace App\Filament\Resources\CryptoNetworks\Pages;

use App\Filament\Resources\CryptoNetworks\CryptoNetworkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCryptoNetworks extends ManageRecords
{
    protected static string $resource = CryptoNetworkResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
