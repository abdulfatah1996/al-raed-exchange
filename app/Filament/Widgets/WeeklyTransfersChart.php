<?php

namespace App\Filament\Widgets;

use App\Support\DashboardData;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class WeeklyTransfersChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string|Htmlable|null
    {
        return __('dashboard.weekly');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $w = DashboardData::weekly();

        return [
            'datasets' => [[
                'label' => __('dashboard.count_label'),
                'data' => $w['data'],
                'fill' => true,
                'tension' => 0,
                'borderColor' => '#1D7CF2',
                'backgroundColor' => 'rgba(29, 124, 242, 0.15)',
                'borderWidth' => 2.5,
                'pointRadius' => 5,
                'pointHoverRadius' => 7,
                'pointBackgroundColor' => '#ffffff',
                'pointBorderColor' => '#1D7CF2',
                'pointBorderWidth' => 2,
            ]],
            'labels' => $w['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => 'rgba(100,116,139,.15)'],
                        'title' => ['display' => true, 'text' => __('dashboard.count_label')]],
                'x' => ['grid' => ['display' => false]],
            ],
        ];
    }
}
