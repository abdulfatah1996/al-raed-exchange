<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    public static function getNavigationLabel(): string
    {
        return __('dashboard.nav_home');
    }

    public function getTitle(): string|Htmlable
    {
        return __('dashboard.title');
    }

    public function getHeading(): string|Htmlable
    {
        return __('dashboard.welcome');
    }

    public function getColumns(): int|array
    {
        return ['md' => 2];
    }
}
