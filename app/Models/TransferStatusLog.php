<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['transfer_id', 'from_status', 'to_status', 'user_id', 'note'];

    protected function casts(): array
    {
        return ['from_status' => TransferStatus::class, 'to_status' => TransferStatus::class, 'created_at' => 'datetime'];
    }

    public function transfer(): BelongsTo { return $this->belongsTo(Transfer::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
