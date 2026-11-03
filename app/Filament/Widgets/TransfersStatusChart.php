<?php

namespace App\Filament\Widgets;

use App\Support\DashboardData;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class TransfersStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string|Htmlable|null
    {
        return __('dashboard.status_heading');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $s = DashboardData::statusBreakdown();

        return [
            'datasets' => [[
                'data' => array_values($s),
                'backgroundColor' => ['#059669', '#2563EB', '#F59E0B', '#E11D48'],
                'borderWidth' => 0,
                'hoverOffset' => 6,
            ]],
            'labels' => array_map(fn (string $k) => __("dashboard.status.{$k}"), array_keys($s)),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '70%',
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]]],
        ];
    }
}
