<?php

namespace App\Filament\Resources\Transfers\RelationManagers;

use App\Filament\Tables\ExpandableRow;
use Filament\Actions\ActionGroup;
use App\Exceptions\InvalidReceiptState;
use App\Models\PaymentAccount;
use App\Enums\ReceiptDelivery;
use App\Enums\ReceiptVerification;
use App\Enums\TransferStatus;
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
        return $table
            ->description(fn () => $this->nextStepHint())
            ->modifyQueryUsing(fn ($query) => $query->with(['currency', 'deliverCurrency', 'paymentAccount', 'deliveryMethod', 'verifier']))
            ->columns(ExpandableRow::columns([
                ExpandableRow::labelled(TextColumn::make('number')->label('#')->sortable()->weight('bold')),
                ExpandableRow::labelled(TextColumn::make('amount')->label(__('fields.amount'))->weight('bold')
                    ->formatStateUsing(fn ($state, Receipt $r) => ExpandableRow::money($state, $r->currency?->code))),
                ExpandableRow::labelled(TextColumn::make('paymentAccount.internal_name')->label(__('fields.payment_account'))->placeholder('—')->wrap()),
                ExpandableRow::labelled(TextColumn::make('verification_status')->label(__('fields.verification_status'))->badge()),
                ExpandableRow::labelled(TextColumn::make('delivery_status')->label(__('fields.delivery_status'))->badge()),
            ], fn (Receipt $r) => [
                __('fields.deliver_amount') => ExpandableRow::money($r->deliver_amount, $r->deliverCurrency?->code),
                __('fields.rate') => $r->rate === null ? null : rtrim(rtrim((string) $r->rate, '0'), '.'),
                __('fields.fee_amount') => ExpandableRow::money($r->fee_amount, null),
                __('fields.delivery_method') => $r->deliveryMethod?->{app()->getLocale() === 'en' ? 'name_en' : 'name_ar'},
                __('fields.external_ref') => ExpandableRow::code($r->external_ref),
                __('fields.sender_name') => $r->sender_name,
                __('fields.receipt_file') => ExpandableRow::link(__('fields.receipt_file'), $r->receipt_file ? route('files.show', ['path' => $r->receipt_file]) : null),
                __('fields.verified_by') => $r->verifier?->name,
                __('admin.common.delivered_at') => $r->delivered_at,
                __('fields.notes') => ExpandableRow::wide($r->notes),
            ]))
            ->defaultSort('number')
            ->headerActions([
                CreateAction::make()->modalWidth('4xl')
                    ->visible(fn () => $this->getOwnerRecord()->acceptsReceipts() && auth()->user()?->can('create', Receipt::class)),
            ])
            ->recordUrl(null)->recordAction(null)
            ->recordActions([ActionGroup::make([
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
            ])->tooltip(__('admin.common.actions'))]);
    }

    /** Confirmation + domain-rule errors shown as a notification instead of a 500 page. */
    /** One line telling staff what to do next with the receipts of this transfer. */
    private function nextStepHint(): ?string
    {
        /** @var \App\Models\Transfer $transfer */
        $transfer = $this->getOwnerRecord();
        $receipts = $transfer->receipts()->get();
        $status = $transfer->status;

        if ($receipts->isEmpty()) {
            return $transfer->acceptsReceipts() ? __('fields.hint.add_receipt') : null;
        }

        $pending = $receipts->where('verification_status', ReceiptVerification::Pending)->count();
        $undelivered = $receipts->where('verification_status', ReceiptVerification::Verified)
            ->where('delivery_status', '!=', ReceiptDelivery::Delivered)->count();

        return match (true) {
            in_array($status, [TransferStatus::Delivered, TransferStatus::Cancelled, TransferStatus::Rejected], true) => null,
            $status->allowsReceiptVerification() && $pending > 0 => __('fields.hint.verify'),
            $status->allowsReceiptVerification() => __('fields.hint.approve'),
            $status->allowsReceiptDelivery() && $undelivered > 0 => __('fields.hint.deliver'),
            $status->allowsReceiptDelivery() => __('fields.hint.close'),
            $pending > 0 => __('fields.hint.move_to_verification'),
            default => null,
        };
    }

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
