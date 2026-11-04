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
        return 'bar';
    }

    protected function getData(): array
    {
        $w = DashboardData::weekly();

        return [
            'datasets' => [[
                'label' => __('dashboard.count_label'),
                'data' => $w['data'],
                // Past days navy, today gold.
                'backgroundColor' => array_map(fn ($i) => $i === count($w['data']) - 1 ? '#c9a227' : '#1c3a63', array_keys($w['data'])),
                'borderRadius' => 6,
                'maxBarThickness' => 34,
            ]],
            'labels' => $w['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => 'rgba(11,31,58,.07)'], 'border' => ['display' => false]],
                'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
            ],
        ];
    }
}
