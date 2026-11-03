<?php

namespace App\Models;

use App\Enums\CurrencyType;
use App\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasLocalizedName;

    protected $fillable = ['code', 'name_ar', 'name_en', 'symbol', 'decimals', 'type', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'decimals' => 'integer', 'type' => CurrencyType::class];
    }
}
