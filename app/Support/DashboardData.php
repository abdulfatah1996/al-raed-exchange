<?php

namespace App\Support;

use App\Enums\TransferStatus;
use App\Models\Customer;
use App\Models\Transfer;

class DashboardData
{
    public static function isDemo(): bool
    {
        return false;
    }

    public static function kpis(): array
    {
        $today = Transfer::whereDate('created_at', today());
        $usdToday = (clone $today)->whereHas('sendCurrency', fn ($q) => $q->where('code', 'USD'))->sum('send_amount');

        return [
            ['label' => __('dashboard.kpi.today'), 'value' => (string) (clone $today)->count(), 'unit' => null, 'icon' => 'heroicon-o-arrows-right-left', 'tone' => 'blue'],
            ['label' => __('dashboard.kpi.total'), 'value' => number_format((float) $usdToday, 2), 'unit' => 'USD', 'icon' => 'heroicon-o-banknotes', 'tone' => 'teal'],
            ['label' => __('dashboard.kpi.new_customers'), 'value' => (string) Customer::where('created_at', '>=', now()->subDays(7))->count(), 'unit' => null, 'icon' => 'heroicon-o-user-plus', 'tone' => 'green'],
            ['label' => __('dashboard.kpi.pending'), 'value' => (string) Transfer::whereIn('status', [TransferStatus::PendingVerification, TransferStatus::UnderReview, TransferStatus::OnHold])->count(), 'unit' => null, 'icon' => 'heroicon-o-clock', 'tone' => 'orange'],
        ];
    }

    public static function recent(): array
    {
        return Transfer::with(['customer', 'sendCurrency'])->latest('id')->take(5)->get()->map(fn (Transfer $t) => [
            'ref' => $t->reference,
            'customer' => $t->customer?->name ?? '—',
            'amount' => $t->send_amount === null ? '—' : rtrim(rtrim(number_format((float) $t->send_amount, 8, '.', ','), '0'), '.') . ' ' . ($t->sendCurrency?->code ?? ''),
            'status' => match ($t->status) {
                TransferStatus::Delivered => 'completed',
                TransferStatus::Cancelled, TransferStatus::Rejected => 'cancelled',
                default => 'review',
            },
            'status_label' => $t->status->getLabel(),
        ])->all();
    }

    public static function weekly(): array
    {
        $labels = $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $labels[] = $day->translatedFormat('l');
            $data[] = Transfer::whereDate('created_at', $day->toDateString())->count();
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
