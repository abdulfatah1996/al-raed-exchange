<?php

namespace App\Models;

use App\Enums\PaymentAccountStatus;
use App\Enums\PaymentAccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAccount extends Model
{
    protected $fillable = [
        'type', 'internal_name', 'service_id', 'country_id', 'currency_id', 'beneficiary_name', 'details', 'status',
        'min_amount', 'max_amount', 'daily_cap', 'instructions_ar', 'instructions_en', 'notes', 'last_verified_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentAccountType::class,
            'status' => PaymentAccountStatus::class,
            'details' => 'encrypted:array',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'daily_cap' => 'decimal:2',
            'last_verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $account) {
            $account->created_by ??= auth()->id();
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', PaymentAccountStatus::Active);
    }

    /** Masked, safe-to-display summary of the sensitive details. */
    public function getMaskedSummaryAttribute(): string
    {
        $d = $this->details ?? [];
        $mask = function (?string $v, int $start = 3, int $end = 3): string {
            $v = trim((string) $v);
            if ($v === '') {
                return '';
            }
            if (mb_strlen($v) <= $start + $end) {
                return str_repeat('•', mb_strlen($v));
            }

            return mb_substr($v, 0, $start) . '…' . mb_substr($v, -$end);
        };
        $email = function (?string $v): string {
            if (! $v || ! str_contains($v, '@')) {
                return '';
            }
            [$u, $dom] = explode('@', $v, 2);

            return mb_substr($u, 0, 2) . '***@' . $dom;
        };

        return match ($this->type) {
            PaymentAccountType::Paypal => $email($d['email'] ?? null),
            PaymentAccountType::Bank => trim(($d['bank_name'] ?? '') . ' ' . $mask($d['iban'] ?? $d['account_number'] ?? null, 4, 4)),
            PaymentAccountType::CryptoWallet => trim(($d['network'] ?? '') . ' ' . $mask($d['address'] ?? null, 5, 4)),
            PaymentAccountType::Cliq => $mask($d['alias'] ?? $d['phone'] ?? null, 2, 2),
            PaymentAccountType::Instapay => $mask($d['address'] ?? $d['phone'] ?? null, 3, 3),
            PaymentAccountType::VodafoneCash, PaymentAccountType::Ewallet => $mask($d['phone'] ?? $d['wallet_id'] ?? null, 3, 3),
            default => $mask($d['extra'] ?? null, 3, 3),
        };
    }
}
