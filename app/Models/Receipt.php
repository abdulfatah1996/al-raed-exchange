<?php

namespace App\Models;

use App\Enums\ReceiptDelivery;
use App\Enums\ReceiptVerification;
use App\Exceptions\InvalidReceiptDelivery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transfer_id', 'number', 'amount', 'currency_id', 'payment_account_id', 'rate', 'fee_amount', 'deliver_amount', 'deliver_currency_id',
        'delivery_method_id', 'external_ref', 'sender_name', 'receipt_file', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8', 'rate' => 'decimal:8', 'fee_amount' => 'decimal:2', 'deliver_amount' => 'decimal:8',
            'verification_status' => ReceiptVerification::class, 'delivery_status' => ReceiptDelivery::class,
            'verified_at' => 'datetime', 'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $r) {
            $r->created_by ??= auth()->id();
            $r->number = (static::withTrashed()->where('transfer_id', $r->transfer_id)->max('number') ?? 0) + 1;
            $r->external_ref = $r->external_ref !== null ? trim($r->external_ref) ?: null : null;
        });
    }

    public function verify(?string $note = null): void
    {
        $this->forceFill(['verification_status' => ReceiptVerification::Verified, 'verified_by' => auth()->id(), 'verified_at' => now(), 'verification_note' => $note])->save();
    }

    public function reject(?string $note = null): void
    {
        $this->forceFill(['verification_status' => ReceiptVerification::Rejected, 'verified_by' => auth()->id(), 'verified_at' => now(), 'verification_note' => $note])->save();
    }

    public function markDelivered(): void
    {
        if ($this->verification_status !== ReceiptVerification::Verified) {
            throw new InvalidReceiptDelivery(__('fields.delivery_blocked'));
        }

        if ($this->delivery_status === ReceiptDelivery::Delivered) {
            return;
        }

        $this->forceFill([
            'delivery_status' => ReceiptDelivery::Delivered,
            'delivered_by' => auth()->id(),
            'delivered_at' => now(),
        ])->save();
    }

    public function transfer(): BelongsTo { return $this->belongsTo(Transfer::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function deliverCurrency(): BelongsTo { return $this->belongsTo(Currency::class, 'deliver_currency_id'); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function deliveryMethod(): BelongsTo { return $this->belongsTo(DeliveryMethod::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
