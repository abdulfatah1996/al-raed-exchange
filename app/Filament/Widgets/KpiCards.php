<?php

namespace App\Filament\Widgets;

use App\Support\DashboardData;
use Filament\Widgets\Widget;

class KpiCards extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.kpi-cards';

    protected function getViewData(): array
    {
        return ['cards' => DashboardData::kpis(), 'demo' => DashboardData::isDemo()];
    }
}
