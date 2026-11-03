<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Dashboard data source. Returns sample data until the transfers table exists;
 * each method will be replaced with real queries in the transfers phase.
 */
class DashboardData
{
    public static function isDemo(): bool
    {
        return ! Schema::hasTable('transfers');
    }

    public static function stats(): array
    {
        return [
            'today' => 18,       'today_delta' => '+22% ' . __('dashboard.vs_yesterday'),        'today_chart' => [7, 9, 8, 12, 11, 15, 18],
            'pending' => 3,      'pending_delta' => '-40% ' . __('dashboard.vs_last_week'),      'pending_chart' => [6, 5, 5, 4, 4, 3, 3],
            'in_progress' => 7,  'in_progress_delta' => '+2 ' . __('dashboard.vs_yesterday'),    'in_progress_chart' => [3, 4, 4, 5, 6, 6, 7],
            'delivered' => 12,   'delivered_delta' => '+8% ' . __('dashboard.vs_yesterday'),     'delivered_chart' => [8, 9, 10, 9, 11, 11, 12],
        ];
    }

    public static function volume(): array
    {
        $labels = [];
        for ($i = 13; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->translatedFormat('d M');
        }

        return ['labels' => $labels, 'data' => [9, 12, 10, 14, 13, 17, 15, 19, 22, 18, 21, 25, 23, 18]];
    }

    public static function statusBreakdown(): array
    {
        return ['delivered' => 28, 'in_progress' => 7, 'pending' => 3, 'cancelled' => 2];
    }
}
