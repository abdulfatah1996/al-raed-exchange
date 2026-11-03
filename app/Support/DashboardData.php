<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/** Sample data until the transfers module exists; each method becomes a real query later. */
class DashboardData
{
    public static function isDemo(): bool
    {
        return ! Schema::hasTable('transfers');
    }

    public static function kpis(): array
    {
        return [
            ['label' => __('dashboard.kpi.today'), 'value' => '24', 'unit' => null, 'icon' => 'heroicon-o-arrows-right-left', 'tone' => 'blue'],
            ['label' => __('dashboard.kpi.total'), 'value' => '1,248,500.00', 'unit' => 'USD', 'icon' => 'heroicon-o-banknotes', 'tone' => 'teal'],
            ['label' => __('dashboard.kpi.new_customers'), 'value' => '8', 'unit' => null, 'icon' => 'heroicon-o-user-plus', 'tone' => 'green'],
            ['label' => __('dashboard.kpi.pending'), 'value' => '3', 'unit' => null, 'icon' => 'heroicon-o-clock', 'tone' => 'orange'],
        ];
    }

    public static function recent(): array
    {
        return [
            ['ref' => '#10458', 'customer' => 'أحمد العبسي', 'amount' => '250,000.00 USD', 'status' => 'completed'],
            ['ref' => '#10457', 'customer' => 'ريم الحارثي', 'amount' => '75,000.00 USD', 'status' => 'review'],
            ['ref' => '#10456', 'customer' => 'خالد البيشي', 'amount' => '500,000.00 USD', 'status' => 'completed'],
            ['ref' => '#10455', 'customer' => 'سارة القحطاني', 'amount' => '120,000.00 USD', 'status' => 'review'],
        ];
    }

    public static function weekly(): array
    {
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->translatedFormat('l');
        }

        return ['labels' => $labels, 'data' => [12, 16, 14, 19, 23, 21, 26]];
    }
}
