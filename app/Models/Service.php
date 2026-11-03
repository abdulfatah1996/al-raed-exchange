<?php

namespace App\Models;

use App\Enums\ServiceDirection;
use App\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasLocalizedName;

    protected $fillable = ['code', 'name_ar', 'name_en', 'description_ar', 'description_en', 'country_id', 'direction', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'direction' => ServiceDirection::class];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
