<?php

namespace App\Filament\Resources\Transfers\RelationManagers;

use App\Enums\ReceiptDelivery;
use App\Enums\ReceiptVerification;
use App\Exceptions\InvalidReceiptDelivery;
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
                ->unique(ignoreRecord: true)->validationMessages(['unique' => __('fields.duplicate_ref')]),
            FileUpload::make('receipt_file')->label(__('fields.receipt_file'))->disk('local')->directory('receipts')->visibility('private')
                ->acceptedFileTypes(['image/*', 'application/pdf'])->maxSize(8192)->openable()->downloadable(),
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
            ->headerActions([CreateAction::make()->modalWidth('4xl')])
            ->recordActions([
                EditAction::make()->modalWidth('4xl')->visible(fn (Receipt $r) => $r->verification_status !== ReceiptVerification::Verified),
                Action::make('verify')->label(__('fields.verify'))->icon('heroicon-o-check-badge')->color('success')
                    ->visible(fn (Receipt $r) => $r->verification_status !== ReceiptVerification::Verified)
                    ->schema([Textarea::make('note')->label(__('fields.verification_note'))->rows(2)])
                    ->requiresConfirmation()
                    ->action(function (Receipt $r, array $data) { $r->verify($data['note'] ?? null); Notification::make()->title(__('fields.verify'))->success()->send(); }),
                Action::make('reject')->label(__('fields.reject'))->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (Receipt $r) => $r->verification_status === ReceiptVerification::Pending)
                    ->schema([Textarea::make('note')->label(__('fields.verification_note'))->rows(2)->required()])
                    ->requiresConfirmation()
                    ->action(function (Receipt $r, array $data) { $r->reject($data['note']); Notification::make()->title(__('fields.reject'))->warning()->send(); }),
                Action::make('deliver')->label(__('fields.mark_delivered'))->icon('heroicon-o-truck')->color('primary')
                    ->visible(fn (Receipt $r) => $r->verification_status === ReceiptVerification::Verified && $r->delivery_status !== ReceiptDelivery::Delivered)
                    ->requiresConfirmation()
                    ->action(function (Receipt $r) {
                        try {
                            $r->markDelivered();
                            Notification::make()->title(__('fields.mark_delivered'))->success()->send();
                        } catch (InvalidReceiptDelivery $e) {
                            Notification::make()->title(__('fields.transition_failed'))->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
