<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReceiptVerification: string implements HasLabel, HasColor
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function getLabel(): string { return __('enums.receipt_verification.' . $this->value); }

    public function getColor(): string
    {
        return match ($this) { self::Pending => 'warning', self::Verified => 'success', self::Rejected => 'danger' };
    }
}
