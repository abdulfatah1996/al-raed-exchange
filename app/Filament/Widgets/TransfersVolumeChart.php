<?php

namespace App\Filament\Widgets;

use App\Support\DashboardData;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class TransfersVolumeChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    public function getHeading(): string|Htmlable|null
    {
        return __('dashboard.volume_heading');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $v = DashboardData::volume();

        return [
            'datasets' => [[
                'label' => __('dashboard.transfers'),
                'data' => $v['data'],
                'fill' => true,
                'tension' => 0.4,
                'borderColor' => '#2563EB',
                'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                'borderWidth' => 2,
                'pointRadius' => 0,
                'pointHoverRadius' => 4,
            ]],
            'labels' => $v['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'grid' => ['color' => 'rgba(0,0,0,0.05)'], 'ticks' => ['precision' => 0]],
                'x' => ['grid' => ['display' => false]],
            ],
        ];
    }
}
