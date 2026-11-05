<?php

namespace App\Filament\Resources\TransferRequests;

use Filament\Actions\ActionGroup;
use App\Filament\Tables\ExpandableRow;
use App\Enums\RequestOperation;
use App\Enums\RequestStatus;
use App\Filament\Resources\TransferRequests\Pages\ListTransferRequests;
use App\Filament\Resources\Transfers\TransferResource;
use App\Models\TransferRequest;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use RuntimeException;

/** Leads from the public website. Read-only except for the handling actions. */
class TransferRequestResource extends Resource
{
    protected static ?string $model = TransferRequest::class;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getNavigationGroup(): string { return __('resources.groups.operations'); }
    public static function getNavigationIcon(): string { return 'heroicon-o-inbox-arrow-down'; }
    public static function getNavigationSort(): int { return 29; }
    public static function getModelLabel(): string { return __('web.admin.request'); }
    public static function getPluralModelLabel(): string { return __('web.admin.requests'); }
    public static function getGloballySearchableAttributes(): array { return ['reference', 'name', 'phone']; }

    public static function getNavigationBadge(): ?string
    {
        $n = TransferRequest::where('status', RequestStatus::New)->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): string { return 'warning'; }

    public static function infolist(Schema $schema): Schema
    {
        $name = app()->getLocale() === 'en' ? 'name_en' : 'name_ar';

        return $schema->columns(3)->components([
            TextEntry::make('reference')->label(__('web.track.reference'))->fontFamily('mono')->copyable()->weight('bold'),
            TextEntry::make('status')->label(__('web.admin.status'))->badge(),
            TextEntry::make('created_at')->label(__('web.admin.received_at'))->dateTime('Y-m-d H:i'),
            TextEntry::make('name')->label(__('web.request.name')),
            TextEntry::make('phone')->label(__('web.request.phone'))->copyable()
                ->url(fn (TransferRequest $r) => 'https://wa.me/' . preg_replace('/\D+/', '', $r->phone), shouldOpenInNewTab: true),
            TextEntry::make('operation')->label(__('web.admin.operation'))->badge()->color('gray'),
            TextEntry::make('amount')->label(__('web.request.amount'))
                ->formatStateUsing(fn ($state, TransferRequest $r) => rtrim(rtrim(number_format((float) $state, 8, '.', ','), '0'), '.') . ' ' . $r->currency?->code),
            TextEntry::make("service.$name")->label(__('web.request.service'))->placeholder('—'),
            TextEntry::make("country.$name")->label(__('fields.country'))->placeholder('—'),
            TextEntry::make("deliveryMethod.$name")->label(__('web.request.delivery'))->placeholder('—'),
            TextEntry::make('receive_via')->label(__('web.request.receive_via'))->placeholder('—'),
            TextEntry::make('handler.name')->label(__('web.admin.handled_by'))->placeholder('—'),
            TextEntry::make('notes')->label(__('web.request.notes'))->placeholder('—')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['country', 'service', 'currency', 'deliveryMethod', 'transfer', 'handler']))
            ->columns(ExpandableRow::columns([
                ExpandableRow::labelled(TextColumn::make('reference')->label(__('web.track.reference'))->searchable()->fontFamily('mono')->weight('bold')),
                ExpandableRow::labelled(TextColumn::make('name')->label(__('web.request.name'))->searchable()->wrap()),
                ExpandableRow::labelled(TextColumn::make('amount')->label(__('web.request.amount'))
                    ->formatStateUsing(fn ($state, TransferRequest $r) => ExpandableRow::money($state, $r->currency?->code))),
                ExpandableRow::labelled(TextColumn::make('status')->label(__('web.admin.status'))->badge()),
                ExpandableRow::labelled(TextColumn::make('created_at')->label(__('web.admin.received_at'))->since()->sortable()),
            ], function (TransferRequest $r) {
                $n = app()->getLocale() === 'en' ? 'name_en' : 'name_ar';

                return [
                    __('web.request.phone') => ExpandableRow::code($r->phone),
                    __('web.admin.operation') => $r->operation,
                    __('web.request.country') => $r->country?->{$n},
                    __('web.request.service') => $r->service?->{$n},
                    __('web.request.delivery') => $r->deliveryMethod?->{$n},
                    __('web.request.receive_via') => $r->receive_via,
                    __('web.admin.received_at') => $r->created_at,
                    __('web.admin.handled_by') => $r->handler?->name,
                    __('admin.common.linked_transfer') => ExpandableRow::code($r->transfer?->reference),
                    __('web.request.notes') => ExpandableRow::wide($r->notes),
                ];
            }))
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('web.admin.status'))->options(RequestStatus::class)->default(RequestStatus::New->value),
                SelectFilter::make('operation')->label(__('web.admin.operation'))->options(RequestOperation::class),
            ])
            ->recordUrl(null)->recordAction(null)
            ->recordActions([ActionGroup::make([
                ViewAction::make(),
                Action::make('whatsapp')->label(__('web.admin.whatsapp'))->icon('heroicon-o-chat-bubble-left-right')->color('success')
                    ->url(fn (TransferRequest $r) => 'https://wa.me/' . preg_replace('/\D+/', '', $r->phone), shouldOpenInNewTab: true),
                Action::make('contacted')->label(__('web.admin.mark_contacted'))->icon('heroicon-o-phone')->color('info')
                    ->authorize('handle')->visible(fn (TransferRequest $r) => $r->status === RequestStatus::New)
                    ->action(fn (TransferRequest $r) => $r->markContacted()),
                Action::make('convert')->label(__('web.admin.convert'))->icon('heroicon-o-arrows-right-left')->color('primary')
                    ->authorize('convert')->requiresConfirmation()
                    ->action(function (TransferRequest $r, Action $action) {
                        try {
                            $transfer = $r->convertToTransfer();
                            Notification::make()->title(__('web.admin.converted') . ': ' . $transfer->reference)->success()->send();
                            $action->redirect(TransferResource::getUrl('edit', ['record' => $transfer]));
                        } catch (RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('openTransfer')->label(__('web.admin.open_transfer'))->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (TransferRequest $r) => $r->transfer_id !== null)
                    ->url(fn (TransferRequest $r) => TransferResource::getUrl('view', ['record' => $r->transfer_id])),
                Action::make('close')->label(__('web.admin.close'))->icon('heroicon-o-x-mark')->color('gray')
                    ->authorize('handle')->requiresConfirmation()
                    ->action(fn (TransferRequest $r) => $r->close()),
            ])->tooltip(__('admin.common.actions'))])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => ListTransferRequests::route('/')];
    }
}
