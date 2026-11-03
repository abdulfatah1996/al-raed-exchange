<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentAccountType: string implements HasLabel
{
    case Paypal = 'paypal';
    case Bank = 'bank';
    case Ewallet = 'ewallet';
    case CryptoWallet = 'crypto_wallet';
    case Cliq = 'cliq';
    case Instapay = 'instapay';
    case VodafoneCash = 'vodafone_cash';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('enums.payment_account_type.' . $this->value);
    }
}
