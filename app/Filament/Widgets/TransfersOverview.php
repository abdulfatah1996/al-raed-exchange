<?php

namespace App\Filament\Widgets;

use App\Support\DashboardData;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransfersOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return DashboardData::isDemo() ? __('dashboard.demo_notice') : null;
    }

    protected function getStats(): array
    {
        $d = DashboardData::stats();

        return [
            Stat::make(__('dashboard.today_transfers'), $d['today'])
                ->description($d['today_delta'])
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-arrows-right-left')
                ->chart($d['today_chart'])
                ->color('success'),
            Stat::make(__('dashboard.pending_check'), $d['pending'])
                ->description($d['pending_delta'])
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->icon('heroicon-o-magnifying-glass-circle')
                ->chart($d['pending_chart'])
                ->color('warning'),
            Stat::make(__('dashboard.in_progress'), $d['in_progress'])
                ->description($d['in_progress_delta'])
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-clock')
                ->chart($d['in_progress_chart'])
                ->color('info'),
            Stat::make(__('dashboard.delivered_today'), $d['delivered'])
                ->description($d['delivered_delta'])
                ->descriptionIcon('heroicon-m-check-badge')
                ->icon('heroicon-o-check-circle')
                ->chart($d['delivered_chart'])
                ->color('primary'),
        ];
    }
}
