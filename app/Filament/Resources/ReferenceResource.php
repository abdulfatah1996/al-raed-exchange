<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

/** Shared behaviour for reference-data resources (settings group, no deletion). */
abstract class ReferenceResource extends Resource
{
    protected static string $labelKey = '';

    public static function getNavigationGroup(): string
    {
        return __('resources.groups.settings');
    }

    public static function getModelLabel(): string
    {
        return __('resources.' . static::$labelKey . '.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.' . static::$labelKey . '.plural');
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function nameColumn(): string
    {
        return app()->getLocale() === 'en' ? 'name_en' : 'name_ar';
    }
}
