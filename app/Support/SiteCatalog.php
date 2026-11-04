<?php

namespace App\Support;

use App\Models\Service;
use Illuminate\Support\Collection;

/** Groups the active services for the public website. New services added from the panel land in "other". */
final class SiteCatalog
{
    /** service-code prefix => group key (order = display order) */
    private const GROUPS = [
        'usdt' => 'crypto', 'paypal' => 'paypal', 'jordan' => 'jordan', 'egypt' => 'egypt', 'eu' => 'europe',
        'turkey' => 'turkey', 'syria' => 'levant', 'lebanon' => 'levant', 'intl' => 'international', 'us' => 'international',
    ];

    /** @return Collection<string, Collection<int, Service>> */
    public static function groupedServices(): Collection
    {
        $order = array_flip(array_values(array_unique([...array_values(self::GROUPS), 'other'])));

        return Service::query()->active()->ordered()->with('country')->get()
            ->groupBy(fn (Service $s) => self::GROUPS[strtok($s->code, '_')] ?? 'other')
            ->sortBy(fn ($items, string $group) => $order[$group] ?? 99);
    }

    public static function whatsappUrl(?string $text = null): string
    {
        return 'https://wa.me/' . preg_replace('/\D+/', '', (string) config('alraed.whatsapp'))
            . ($text ? '?text=' . rawurlencode($text) : '');
    }
}
