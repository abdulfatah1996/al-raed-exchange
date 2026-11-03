<?php

namespace App\Filament\Resources\Transfers\Pages;

use App\Enums\TransferStatus;
use App\Exceptions\InvalidTransferTransition;
use App\Filament\Resources\Transfers\TransferResource;
use App\Models\Transfer;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTransfer extends EditRecord
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeStatus')
                ->label(__('fields.change_status'))
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn () => $this->getRecord()->status->next() !== [])
                ->schema([
                    Select::make('status')->label(__('fields.new_status'))->required()->native(false)
                        ->options(fn () => collect($this->getRecord()->status->next())->mapWithKeys(fn (TransferStatus $s) => [$s->value => $s->getLabel()])->all()),
                    Textarea::make('note')->label(__('fields.status_note'))->rows(2),
                ])
                ->action(function (array $data) {
                    /** @var Transfer $record */
                    $record = $this->getRecord();
                    try {
                        $record->transitionTo(TransferStatus::from($data['status']), $data['note'] ?? null);
                        Notification::make()->title(__('fields.transition_done'))->success()->send();
                        $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
                    } catch (InvalidTransferTransition $e) {
                        Notification::make()->title(__('fields.transition_failed'))->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
