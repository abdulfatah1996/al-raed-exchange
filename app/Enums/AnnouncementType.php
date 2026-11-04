<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AnnouncementType: string implements HasColor, HasLabel
{
    case Info = 'info';
    case Offer = 'offer';
    case Warning = 'warning';
    case AccountChange = 'account_change';

    public function getLabel(): string
    {
        return __('web.announcement_type.' . $this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Offer => 'success',
            self::Warning => 'danger',
            self::AccountChange => 'warning',
        };
    }
}
