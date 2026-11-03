<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum KycStatus: string implements HasLabel, HasColor
{
    case None = 'none';
    case Pending = 'pending';
    case Verified = 'verified';

    public function getLabel(): string
    {
        return __('enums.kyc_status.' . $this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::Pending => 'warning',
            self::Verified => 'success',
        };
    }
}
