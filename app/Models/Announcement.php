<?php

namespace App\Models;

use App\Enums\AnnouncementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'type', 'title_ar', 'title_en', 'body_ar', 'body_en', 'is_published', 'is_pinned', 'published_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AnnouncementType::class,
            'is_published' => 'boolean',
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $a) => $a->created_by ??= auth()->id());
        static::saving(function (self $a) {
            if ($a->is_published && ! $a->published_at) {
                $a->published_at = now();
            }
        });
    }

    /** Published, already live, not expired; pinned first, newest first. */
    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderByDesc('is_pinned')->orderByDesc('published_at');
    }

    public function getTitleAttribute(): string
    {
        return (app()->getLocale() === 'en' && $this->title_en) ? $this->title_en : $this->title_ar;
    }

    public function getBodyAttribute(): string
    {
        return (app()->getLocale() === 'en' && $this->body_en) ? $this->body_en : $this->body_ar;
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
