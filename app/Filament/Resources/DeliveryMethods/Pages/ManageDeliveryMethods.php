<?php

namespace App\Filament\Resources\DeliveryMethods\Pages;

use App\Filament\Resources\DeliveryMethods\DeliveryMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDeliveryMethods extends ManageRecords
{
    protected static string $resource = DeliveryMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
