<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CustomerStatus: string implements HasLabel, HasColor
{
    case Active = 'active';
    case Vip = 'vip';
    case Blocked = 'blocked';

    public function getLabel(): string
    {
        return __('enums.customer_status.' . $this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Vip => 'warning',
            self::Blocked => 'danger',
        };
    }
}
