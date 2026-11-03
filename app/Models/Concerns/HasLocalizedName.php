<?php

namespace App\Models\Concerns;

trait HasLocalizedName
{
    /** Name in the current UI language, falling back to the other one. */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->attributes["name_{$locale}"] ?? $this->attributes['name_ar'] ?? $this->attributes['name_en'] ?? '';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('name_ar');
    }
}
