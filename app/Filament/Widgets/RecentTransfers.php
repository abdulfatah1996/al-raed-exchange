<?php

namespace App\Filament\Widgets;

use App\Support\DashboardData;
use Filament\Widgets\Widget;

class RecentTransfers extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.recent-transfers';

    protected function getViewData(): array
    {
        return ['rows' => DashboardData::recent(), 'demo' => DashboardData::isDemo()];
    }
}
