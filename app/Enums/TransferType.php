<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TransferType: string implements HasLabel
{
    case Receive = 'receive';
    case Send = 'send';
    case UsdtWithdraw = 'usdt_withdraw';
    case UsdtDeposit = 'usdt_deposit';
    case Exchange = 'exchange';
    case BankDelivery = 'bank_delivery';
    case WalletDelivery = 'wallet_delivery';
    case International = 'international';
    case Adjustment = 'adjustment';

    public function getLabel(): string
    {
        return __('enums.transfer_type.' . $this->value);
    }
}
