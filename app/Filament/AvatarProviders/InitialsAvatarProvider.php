<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Initials avatar rendered locally as an SVG data URI.
 * Replaces Filament's default, which sends every staff name to ui-avatars.com.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = collect(preg_split('/\s+/u', trim((string) Filament::getNameForDefaultAvatar($record))))
            ->filter()->take(2)
            ->map(fn (string $w) => mb_substr(preg_replace('/^[^\p{L}\p{N}]+/u', '', $w), 0, 1))
            ->join('');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            . '<rect width="64" height="64" rx="32" fill="#12294a"/>'
            . '<text x="32" y="33" text-anchor="middle" dominant-baseline="central" font-family="Cairo,Tahoma,sans-serif" font-size="26" font-weight="700" fill="#e6cc7a">'
            . e($initials ?: '•') . '</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
