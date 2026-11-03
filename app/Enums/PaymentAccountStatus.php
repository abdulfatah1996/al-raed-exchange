<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentAccountStatus: string implements HasLabel, HasColor
{
    case Active = 'active';
    case Paused = 'paused';
    case Retired = 'retired';

    public function getLabel(): string
    {
        return __('enums.payment_account_status.' . $this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Paused => 'warning',
            self::Retired => 'gray',
        };
    }
}
