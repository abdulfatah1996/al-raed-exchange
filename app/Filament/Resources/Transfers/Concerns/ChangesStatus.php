<?php

namespace App\Filament\Resources\Transfers\Concerns;

use App\Enums\TransferStatus;
use App\Exceptions\InvalidTransferTransition;
use App\Models\Transfer;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

/** "Change status" header action, shared by the view and edit pages of a transfer. */
trait ChangesStatus
{
    protected function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label(__('fields.change_status'))
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->visible(fn () => $this->transitionOptions() !== [])
            ->schema([
                Select::make('status')->label(__('fields.new_status'))->required()->native(false)
                    ->options(fn () => $this->transitionOptions()),
                Textarea::make('note')->label(__('fields.status_note'))->rows(2),
            ])
            ->action(function (array $data) {
                /** @var Transfer $record */
                $record = $this->getRecord();
                $to = TransferStatus::from($data['status']);
                abort_unless(auth()->user()->can('transition', [$record, $to]), 403);
                try {
                    $record->transitionTo($to, $data['note'] ?? null);
                    Notification::make()->title(__('fields.transition_done'))->success()->send();
                    $page = $this instanceof \Filament\Resources\Pages\EditRecord ? 'edit' : 'view';
                    $this->redirect(static::getResource()::getUrl($page, ['record' => $record]));
                } catch (InvalidTransferTransition $e) {
                    Notification::make()->title(__('fields.transition_failed'))->body($e->getMessage())->danger()->send();
                }
            });
    }

    /** Next statuses the current user is allowed to choose. @return array<string, string> */
    protected function transitionOptions(): array
    {
        /** @var Transfer $record */
        $record = $this->getRecord();

        return collect($record->allowedNextStatuses())
            ->filter(fn (TransferStatus $s) => auth()->user()?->can('transition', [$record, $s]))
            ->mapWithKeys(fn (TransferStatus $s) => [$s->value => $s->getLabel()])
            ->all();
    }
}
