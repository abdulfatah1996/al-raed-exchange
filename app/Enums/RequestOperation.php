<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** What the customer wants to do, in their words. */
enum RequestOperation: string implements HasLabel
{
    case Receive = 'receive';
    case Send = 'send';
    case Withdraw = 'withdraw';
    case Deposit = 'deposit';

    public function getLabel(): string
    {
        return __('web.operation.' . $this->value);
    }

    public function transferType(): TransferType
    {
        return match ($this) {
            self::Receive => TransferType::Receive,
            self::Send => TransferType::Send,
            self::Withdraw => TransferType::UsdtWithdraw,
            self::Deposit => TransferType::UsdtDeposit,
        };
    }
}
