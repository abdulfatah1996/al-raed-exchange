<?php

namespace App\Models;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Exceptions\InvalidTransferTransition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Transfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id', 'service_id', 'country_id', 'type',
        'send_amount', 'send_currency_id', 'deliver_amount', 'deliver_currency_id', 'rate', 'fee_amount', 'fee_currency_id', 'commission_amount',
        'delivery_method_id', 'payment_account_id', 'sender_name', 'beneficiary_name', 'beneficiary_details', 'external_ref',
        'assigned_user_id', 'scheduled_at', 'internal_notes', 'delivery_proof_file', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransferType::class,
            'status' => TransferStatus::class,
            'beneficiary_details' => 'encrypted:array',
            'send_amount' => 'decimal:8',
            'deliver_amount' => 'decimal:8',
            'rate' => 'decimal:8',
            'fee_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'priced_at' => 'datetime', 'approved_at' => 'datetime', 'delivered_at' => 'datetime', 'scheduled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $t) {
            $t->created_by ??= auth()->id();
            $t->status ??= TransferStatus::New;
            $t->reference ??= static::nextReference();
        });

        static::created(function (self $t) {
            $t->statusLogs()->create(['from_status' => null, 'to_status' => $t->status->value, 'user_id' => auth()->id()]);
        });
    }

    /** TR20261103-0001 — daily sequence, generated inside a row lock so two staff can't get the same number. */
    public static function nextReference(): string
    {
        $cfg = config('alraed.transfer_reference');
        $prefix = $cfg['prefix'] . now()->format($cfg['date_format']) . '-';

        return DB::transaction(function () use ($prefix, $cfg) {
            $last = static::withTrashed()->where('reference', 'like', $prefix . '%')->lockForUpdate()->orderByDesc('reference')->value('reference');
            $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            return $prefix . str_pad((string) $seq, $cfg['sequence_digits'], '0', STR_PAD_LEFT);
        });
    }

    /** The only legal way to change a transfer's status. */
    public function transitionTo(TransferStatus $to, ?string $note = null): void
    {
        $from = $this->status;

        if (! in_array($to, $from->next(), true)) {
            throw new InvalidTransferTransition("{$from->value} → {$to->value}");
        }

        if ($to === TransferStatus::Delivered && ! $this->canBeDelivered()) {
            throw new InvalidTransferTransition(__('fields.delivery_blocked'));
        }

        DB::transaction(function () use ($from, $to, $note) {
            $this->status = $to;
            $uid = auth()->id();
            match ($to) {
                TransferStatus::PendingCustomerApproval => $this->forceFill(['priced_by' => $uid, 'priced_at' => now()]),
                TransferStatus::Approved => $this->forceFill(['approved_by' => $uid, 'approved_at' => now()]),
                TransferStatus::Delivered => $this->forceFill(['delivered_by' => $uid, 'delivered_at' => now()]),
                default => null,
            };
            $this->save();
            $this->statusLogs()->create(['from_status' => $from->value, 'to_status' => $to->value, 'user_id' => $uid, 'note' => $note]);
        });
    }

    /** Delivery requires: at least one receipt, every receipt verified, and a delivery proof file. */
    public function canBeDelivered(): bool
    {
        $receipts = $this->receipts()->get();

        return $receipts->isNotEmpty()
            && $receipts->every(fn (Receipt $r) => $r->verification_status === \App\Enums\ReceiptVerification::Verified)
            && filled($this->delivery_proof_file);
    }

    public function receipts(): HasMany { return $this->hasMany(Receipt::class)->orderBy('number'); }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function sendCurrency(): BelongsTo { return $this->belongsTo(Currency::class, 'send_currency_id'); }
    public function deliverCurrency(): BelongsTo { return $this->belongsTo(Currency::class, 'deliver_currency_id'); }
    public function feeCurrency(): BelongsTo { return $this->belongsTo(Currency::class, 'fee_currency_id'); }
    public function deliveryMethod(): BelongsTo { return $this->belongsTo(DeliveryMethod::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function statusLogs(): HasMany { return $this->hasMany(TransferStatusLog::class)->latest('id'); }
}
