<?php

namespace App\Models;

use App\Enums\RequestOperation;
use App\Enums\RequestStatus;
use App\Enums\TransferStatus;
use App\Notifications\StaffNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** A request sent from the public website. It is a lead, not money: staff turn it into a transfer. */
class TransferRequest extends Model
{
    protected $fillable = [
        'name', 'phone', 'country_id', 'service_id', 'operation', 'amount', 'currency_id',
        'delivery_method_id', 'receive_via', 'notes', 'locale',
    ];

    protected $hidden = ['ip_hash', 'phone_digits'];

    protected function casts(): array
    {
        return [
            'operation' => RequestOperation::class,
            'status' => RequestStatus::class,
            'amount' => 'decimal:8',
            'handled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $r) {
            $r->status ??= RequestStatus::New;
            $r->phone_digits = static::digits($r->phone);
            $r->reference ??= static::newReference();
        });

        static::created(fn (self $r) => StaffNotifier::newWebsiteRequest($r));
    }

    /** Only the digits of a phone number, last 9 kept, so "+970 59-777" and "0597 77" match. */
    public static function digits(?string $phone): string
    {
        return substr(preg_replace('/\D+/', '', (string) $phone), -9);
    }

    /** RQ-XXXXXXX — random, not sequential, so references cannot be guessed. */
    public static function newReference(): string
    {
        do {
            $ref = 'RQ-' . Str::upper(Str::random(7));
            $ref = strtr($ref, ['0' => '7', 'O' => 'K', 'I' => 'M', 'L' => 'N', '1' => '9']);
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public static function track(string $reference, string $phone): ?self
    {
        $digits = static::digits($phone);

        return strlen($digits) < 7 ? null : static::with('transfer')
            ->where('reference', Str::upper(trim($reference)))
            ->where('phone_digits', $digits)
            ->first();
    }

    /**
     * The customer-facing stage (1–5) and its label key — never internal details.
     *
     * @return array{step:int, key:string}
     */
    public function publicStage(): array
    {
        if ($this->status === RequestStatus::Closed) {
            return ['step' => 0, 'key' => 'closed'];
        }

        $s = $this->transfer?->status;

        return match (true) {
            $s === TransferStatus::Delivered => ['step' => 5, 'key' => 'delivered'],
            $s === TransferStatus::Cancelled || $s === TransferStatus::Rejected => ['step' => 0, 'key' => 'closed'],
            in_array($s, [TransferStatus::Approved, TransferStatus::InProgress], true) => ['step' => 4, 'key' => 'executing'],
            in_array($s, [TransferStatus::PendingTransfer, TransferStatus::PendingVerification, TransferStatus::UnderReview], true) => ['step' => 3, 'key' => 'verifying'],
            $s !== null || $this->status === RequestStatus::Contacted => ['step' => 2, 'key' => 'pricing'],
            default => ['step' => 1, 'key' => 'received'],
        };
    }

    public function markContacted(): void
    {
        $this->forceFill(['status' => RequestStatus::Contacted, 'handled_by' => auth()->id(), 'handled_at' => now()])->save();
    }

    public function close(): void
    {
        $this->forceFill(['status' => RequestStatus::Closed, 'handled_by' => auth()->id(), 'handled_at' => now()])->save();
    }

    /** Creates (or reuses, by phone) the customer and opens a new transfer for staff to price. */
    public function convertToTransfer(): Transfer
    {
        return DB::transaction(function () {
            /** @var self $fresh */
            $fresh = static::query()->lockForUpdate()->findOrFail($this->getKey());
            if ($fresh->transfer_id) {
                throw new RuntimeException(__('web.admin.already_converted'));
            }

            $customer = Customer::query()
                ->where(fn ($q) => $q->where('phone', $fresh->phone)->orWhere('whatsapp', $fresh->phone))
                ->first()
                ?? Customer::create(['name' => $fresh->name, 'phone' => $fresh->phone, 'country_id' => $fresh->country_id]);

            $transfer = Transfer::create([
                'customer_id' => $customer->id,
                'service_id' => $fresh->service_id,
                'country_id' => $fresh->country_id,
                'type' => $fresh->operation->transferType(),
                'send_amount' => $fresh->amount,
                'send_currency_id' => $fresh->currency_id,
                'delivery_method_id' => $fresh->delivery_method_id,
                'sender_name' => $fresh->name,
                'internal_notes' => trim("{$fresh->reference}\n{$fresh->receive_via}\n{$fresh->notes}"),
            ]);

            $fresh->forceFill([
                'status' => RequestStatus::Converted, 'transfer_id' => $transfer->id,
                'handled_by' => auth()->id(), 'handled_at' => now(),
            ])->save();
            $this->setRawAttributes($fresh->getAttributes(), true);

            return $transfer;
        });
    }

    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function deliveryMethod(): BelongsTo { return $this->belongsTo(DeliveryMethod::class); }
    public function transfer(): BelongsTo { return $this->belongsTo(Transfer::class); }
    public function handler(): BelongsTo { return $this->belongsTo(User::class, 'handled_by'); }
}
