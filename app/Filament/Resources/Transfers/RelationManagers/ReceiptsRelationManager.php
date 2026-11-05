<?php

namespace App\Filament\Resources\Transfers\RelationManagers;

use App\Exceptions\InvalidReceiptState;
use App\Models\PaymentAccount;
use App\Models\Receipt;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReceiptsRelationManager extends RelationManager
{
    protected static string $relationship = 'receipts';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('fields.receipts');
    }

    public static function getModelLabel(): string
    {
        return __('fields.receipt');
    }

    public static function getPluralModelLabel(): string
    {
        return __('fields.receipts');
    }

    public function form(Schema $schema): Schema
    {
        $service = $this->getOwnerRecord()->service_id;

        return $schema->columns(3)->components([
            TextInput::make('amount')->label(__('fields.amount'))->numeric()->inputMode('decimal')->minValue(0)->required(),
            Select::make('currency_id')->label(__('fields.currency'))->relationship('currency', 'code')->searchable()->preload()->native(false)->required(),
            Select::make('payment_account_id')->label(__('fields.payment_account'))->searchable()->required()
                ->options(fn () => PaymentAccount::active()->when($service, fn ($q) => $q->where('service_id', $service))->orderBy('internal_name')->pluck('internal_name', 'id')->all()),
            TextInput::make('rate')->label(__('fields.rate'))->numeric()->inputMode('decimal')->minValue(0),
            TextInput::make('fee_amount')->label(__('fields.fee_amount'))->numeric()->inputMode('decimal')->minValue(0),
            TextInput::make('deliver_amount')->label(__('fields.deliver_amount'))->numeric()->inputMode('decimal')->minValue(0),
            Select::make('deliver_currency_id')->label(__('fields.deliver_currency'))->relationship('deliverCurrency', 'code')->searchable()->preload()->native(false),
            Select::make('delivery_method_id')->label(__('fields.delivery_method'))->relationship('deliveryMethod', app()->getLocale() === 'en' ? 'name_en' : 'name_ar')->searchable()->preload()->native(false),
            TextInput::make('sender_name')->label(__('fields.sender_name'))->maxLength(150),
            TextInput::make('external_ref')->label(__('fields.external_ref'))->maxLength(150)->columnSpan(2)
                ->rule(fn (?Receipt $record) => function (string $attribute, $value, \Closure $fail) use ($record) {
                    if (Receipt::refIsTaken($value, $record?->getKey())) {
                        $fail(__('fields.duplicate_ref'));
                    }
                }),
            FileUpload::make('receipt_file')->label(__('fields.receipt_file'))->disk('local')->directory('receipts')->visibility('private')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])->maxSize(8192)->openable()->downloadable(),
            Textarea::make('notes')->label(__('fields.notes'))->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $money = fn ($state, ?string $code) => $state === null ? '—' : rtrim(rtrim(number_format((float) $state, 8, '.', ','), '0'), '.') . ' ' . ($code ?? '');

        return $table
            ->columns([
                TextColumn::make('number')->label('#')->sortable(),
                TextColumn::make('amount')->label(__('fields.amount'))->formatStateUsing(fn ($state, Receipt $r) => $money($state, $r->currency?->code))->weight('bold'),
                TextColumn::make('paymentAccount.internal_name')->label(__('fields.payment_account'))->placeholder('—'),
                TextColumn::make('deliver_amount')->label(__('fields.deliver_amount'))->formatStateUsing(fn ($state, Receipt $r) => $money($state, $r->deliverCurrency?->code)),
                TextColumn::make('external_ref')->label(__('fields.external_ref'))->fontFamily('mono')->copyable()->placeholder('—')->limit(18),
                TextColumn::make('receipt_file')->label(__('fields.receipt_file'))->placeholder('—')->formatStateUsing(fn () => '📎')
                    ->url(fn (Receipt $r) => $r->receipt_file ? route('files.show', ['path' => $r->receipt_file]) : null, shouldOpenInNewTab: true),
                TextColumn::make('verification_status')->label(__('fields.verification_status'))->badge(),
                TextColumn::make('delivery_status')->label(__('fields.delivery_status'))->badge(),
                TextColumn::make('verifier.name')->label(__('fields.verified_by'))->placeholder('—')->toggleable(),
            ])
            ->defaultSort('number')
            ->headerActions([
                CreateAction::make()->modalWidth('4xl')
                    ->visible(fn () => $this->getOwnerRecord()->acceptsReceipts() && auth()->user()?->can('create', Receipt::class)),
            ])
            ->recordActions([
                EditAction::make()->modalWidth('4xl')->authorize('update'),
                $this->guarded(Action::make('verify')->label(__('fields.verify'))->icon('heroicon-o-check-badge')->color('success')
                    ->authorize('verify')
                    ->schema([Textarea::make('note')->label(__('fields.verification_note'))->rows(2)]),
                    fn (Receipt $r, array $data) => $r->verify($data['note'] ?? null), 'success'),
                $this->guarded(Action::make('reject')->label(__('fields.reject'))->icon('heroicon-o-x-circle')->color('danger')
                    ->authorize('reject')
                    ->schema([Textarea::make('note')->label(__('fields.verification_note'))->rows(2)->required()]),
                    fn (Receipt $r, array $data) => $r->reject($data['note']), 'warning'),
                $this->guarded(Action::make('deliver')->label(__('fields.mark_delivered'))->icon('heroicon-o-truck')->color('primary')
                    ->authorize('deliver'),
                    fn (Receipt $r) => $r->markDelivered(), 'success'),
            ]);
    }

    /** Confirmation + domain-rule errors shown as a notification instead of a 500 page. */
    private function guarded(Action $action, \Closure $run, string $tone): Action
    {
        return $action->requiresConfirmation()->action(function (Receipt $record, array $data) use ($action, $run, $tone) {
            try {
                $run($record, $data);
                Notification::make()->title($action->getLabel())->status($tone)->send();
            } catch (InvalidReceiptState $e) {
                Notification::make()->title(__('fields.transition_failed'))->body($e->getMessage())->danger()->send();
            }
        });
    }
}
