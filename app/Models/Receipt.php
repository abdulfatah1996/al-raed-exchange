<?php

namespace App\Models;

use App\Enums\ReceiptDelivery;
use App\Enums\ReceiptVerification;
use App\Exceptions\InvalidReceiptDelivery;
use App\Exceptions\InvalidReceiptState;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use Concerns\Auditable;

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

    /** Financial fields frozen once the receipt is verified. */
    private const FROZEN_AFTER_VERIFY = [
        'transfer_id', 'amount', 'currency_id', 'payment_account_id', 'rate', 'fee_amount', 'deliver_amount',
        'deliver_currency_id', 'external_ref', 'sender_name', 'receipt_file',
    ];

    /**
     * TXID / bank reference in canonical form: no whitespace, upper-case.
     * "abc 123" and "ABC123" are the same payment and must collide on the unique index.
     */
    public static function normalizeRef(?string $ref): ?string
    {
        $ref = preg_replace('/\s+/u', '', (string) $ref);

        return $ref === '' ? null : mb_strtoupper($ref);
    }

    /** Is this reference already used by another receipt (including deleted ones)? */
    public static function refIsTaken(?string $ref, ?int $ignoreId = null): bool
    {
        $ref = static::normalizeRef($ref);

        return $ref !== null && static::withTrashed()->where('external_ref', $ref)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists();
    }

    protected function externalRef(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => static::normalizeRef($value));
    }

    protected static function booted(): void
    {
        static::creating(function (self $r) {
            $transfer = Transfer::query()->findOrFail($r->transfer_id);
            if (! $transfer->acceptsReceipts()) {
                throw new InvalidReceiptState(__('fields.receipts_closed'));
            }

            $r->created_by ??= auth()->id();
            $r->number = (static::withTrashed()->where('transfer_id', $r->transfer_id)->max('number') ?? 0) + 1;
        });

        static::updating(function (self $r) {
            $dirty = array_keys($r->getDirty());
            $wasVerified = $r->getRawOriginal('verification_status') === ReceiptVerification::Verified->value;

            if ($wasVerified && array_intersect($dirty, self::FROZEN_AFTER_VERIFY) !== []) {
                throw new InvalidReceiptState(__('fields.receipt_locked'));
            }

            $statusOnly = array_diff($dirty, ['verification_status', 'verified_by', 'verified_at', 'verification_note',
                'delivery_status', 'delivered_by', 'delivered_at', 'notes', 'updated_at']) === [];
            if (! $statusOnly && $r->transfer()->first()?->isFinanciallyLocked()) {
                throw new InvalidReceiptState(__('fields.receipt_locked'));
            }
        });

        static::deleting(function (self $r) {
            if ($r->verification_status !== ReceiptVerification::Pending || $r->transfer()->first()?->isFinanciallyLocked()) {
                throw new InvalidReceiptState(__('fields.receipt_locked'));
            }
        });
    }

    public function canBeVerified(): bool
    {
        return $this->verification_status === ReceiptVerification::Pending
            && (bool) $this->transfer?->status->allowsReceiptVerification();
    }

    public function canBeDelivered(): bool
    {
        return $this->verification_status === ReceiptVerification::Verified
            && $this->delivery_status !== ReceiptDelivery::Delivered
            && (bool) $this->transfer?->status->allowsReceiptDelivery();
    }

    public function verify(?string $note = null): void
    {
        $this->decide(ReceiptVerification::Verified, $note);
    }

    public function reject(?string $note = null): void
    {
        $this->decide(ReceiptVerification::Rejected, $note);
    }

    private function decide(ReceiptVerification $result, ?string $note): void
    {
        DB::transaction(function () use ($result, $note) {
            $fresh = $this->lockedFresh();
            if (! $fresh->canBeVerified()) {
                throw new InvalidReceiptState(__('fields.receipt_not_verifiable'));
            }

            $fresh->forceFill(['verification_status' => $result, 'verified_by' => auth()->id(), 'verified_at' => now(), 'verification_note' => $note])->save();
            $this->setRawAttributes($fresh->getAttributes(), true);
        });
    }

    public function markDelivered(): void
    {
        DB::transaction(function () {
            $fresh = $this->lockedFresh();

            if ($fresh->delivery_status === ReceiptDelivery::Delivered) {
                $this->setRawAttributes($fresh->getAttributes(), true);

                return;
            }

            if (! $fresh->canBeDelivered()) {
                throw new InvalidReceiptDelivery(__('fields.receipt_not_deliverable'));
            }

            $fresh->forceFill(['delivery_status' => ReceiptDelivery::Delivered, 'delivered_by' => auth()->id(), 'delivered_at' => now()])->save();
            $this->setRawAttributes($fresh->getAttributes(), true);
        });
    }

    /** Re-read this receipt and its transfer under row locks (call inside a transaction). */
    private function lockedFresh(): self
    {
        /** @var self $fresh */
        $fresh = static::query()->lockForUpdate()->findOrFail($this->getKey());
        $fresh->setRelation('transfer', Transfer::query()->lockForUpdate()->findOrFail($fresh->transfer_id));

        return $fresh;
    }

    public function transfer(): BelongsTo { return $this->belongsTo(Transfer::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function deliverCurrency(): BelongsTo { return $this->belongsTo(Currency::class, 'deliver_currency_id'); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function deliveryMethod(): BelongsTo { return $this->belongsTo(DeliveryMethod::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
