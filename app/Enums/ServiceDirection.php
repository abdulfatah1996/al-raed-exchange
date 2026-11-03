<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ServiceDirection: string implements HasLabel
{
    case Receive = 'receive';
    case Send = 'send';
    case Both = 'both';

    public function getLabel(): string
    {
        return __('enums.service_direction.' . $this->value);
    }
}
