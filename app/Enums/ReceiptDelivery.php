<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReceiptDelivery: string implements HasLabel, HasColor
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function getLabel(): string { return __('enums.receipt_delivery.' . $this->value); }

    public function getColor(): string
    {
        return match ($this) { self::Pending => 'gray', self::Delivered => 'success', self::Failed => 'danger' };
    }
}
