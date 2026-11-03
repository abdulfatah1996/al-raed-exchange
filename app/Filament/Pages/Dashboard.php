<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    public function getSubheading(): string|Htmlable|null
    {
        return __('dashboard.subheading');
    }

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 3];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newTransfer')
                ->label(__('dashboard.new_transfer'))
                ->icon('heroicon-m-plus')
                ->action(fn () => Notification::make()->title(__('dashboard.coming_soon'))->info()->send()),
        ];
    }
}
