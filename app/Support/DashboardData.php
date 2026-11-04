<?php

namespace App\Support;

use App\Enums\RequestStatus;
use App\Enums\TransferStatus;
use App\Filament\Resources\TransferRequests\TransferRequestResource;
use App\Filament\Resources\Transfers\TransferResource;
use App\Models\TransferRequest;
use App\Models\Transfer;

class DashboardData
{
    public static function isDemo(): bool
    {
        return false;
    }

    /** Count-based KPIs that tell staff what needs action now (no mixed-currency totals). */
    public static function kpis(): array
    {
        $count = fn (array $statuses) => (string) Transfer::whereIn('status', $statuses)->count();
        $transfers = fn (array $statuses) => TransferResource::getUrl('index', ['filters' => ['status' => ['values' => array_map(fn ($s) => $s->value, $statuses)]]]);

        return [
            ['label' => __('dashboard.kpi_requests'), 'value' => (string) TransferRequest::where('status', RequestStatus::New)->count(),
                'icon' => 'heroicon-o-inbox-arrow-down', 'tone' => 'gold', 'url' => TransferRequestResource::getUrl('index')],
            ['label' => __('dashboard.kpi_verify'), 'value' => $count([TransferStatus::PendingVerification, TransferStatus::UnderReview]),
                'icon' => 'heroicon-o-shield-check', 'tone' => 'amber', 'url' => $transfers([TransferStatus::PendingVerification, TransferStatus::UnderReview])],
            ['label' => __('dashboard.kpi_execute'), 'value' => $count([TransferStatus::Approved, TransferStatus::InProgress]),
                'icon' => 'heroicon-o-truck', 'tone' => 'navy', 'url' => $transfers([TransferStatus::Approved, TransferStatus::InProgress])],
            ['label' => __('dashboard.kpi_delivered'), 'value' => (string) Transfer::where('status', TransferStatus::Delivered)->whereDate('delivered_at', today())->count(),
                'icon' => 'heroicon-o-check-badge', 'tone' => 'green', 'url' => $transfers([TransferStatus::Delivered])],
        ];
    }

    public static function recent(): array
    {
        return Transfer::with(['customer', 'sendCurrency'])->latest('id')->take(5)->get()->map(fn (Transfer $t) => [
            'ref' => $t->reference,
            'customer' => $t->customer?->name ?? '—',
            'amount' => $t->send_amount === null ? '—' : rtrim(rtrim(number_format((float) $t->send_amount, 8, '.', ','), '0'), '.') . ' ' . ($t->sendCurrency?->code ?? ''),
            'status' => $t->status->getColor(),
            'url' => TransferResource::getUrl('view', ['record' => $t]),
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
