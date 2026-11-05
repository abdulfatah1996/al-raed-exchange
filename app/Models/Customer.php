<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use Concerns\Auditable;

    use SoftDeletes;

    protected $fillable = ['code', 'name', 'phone', 'whatsapp', 'country_id', 'status', 'kyc_status', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['status' => CustomerStatus::class, 'kyc_status' => KycStatus::class];
    }

    protected static function booted(): void
    {
        static::creating(function (self $customer) {
            $customer->created_by ??= auth()->id();
            $customer->whatsapp = $customer->whatsapp ?: $customer->phone;
        });

        static::created(function (self $customer) {
            if (! $customer->code) {
                $customer->code = 'C-' . str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT);
                $customer->saveQuietly();
            }
        });
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notesLog(): HasMany
    {
        return $this->hasMany(CustomerNote::class)->latest();
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        $n = preg_replace('/\D+/', '', (string) ($this->whatsapp ?: $this->phone));

        return $n ? "https://wa.me/{$n}" : null;
    }
}
