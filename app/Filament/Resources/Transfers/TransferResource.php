<?php

namespace App\Filament\Resources\Transfers;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Filament\Resources\Transfers\Pages\CreateTransfer;
use App\Filament\Resources\Transfers\Pages\EditTransfer;
use App\Filament\Resources\Transfers\Pages\ListTransfers;
use App\Filament\Resources\Transfers\Pages\ViewTransfer;
use App\Filament\Resources\Transfers\RelationManagers\ReceiptsRelationManager;
use App\Filament\Resources\Transfers\RelationManagers\StatusLogsRelationManager;
use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Transfer;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransferResource extends Resource
{
    protected static ?string $model = Transfer::class;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getNavigationGroup(): string { return __('resources.groups.operations'); }
    public static function getNavigationIcon(): string { return 'heroicon-o-arrows-right-left'; }
    public static function getNavigationSort(): int { return 31; }
    public static function getModelLabel(): string { return __('resources.transfer.singular'); }
    public static function getPluralModelLabel(): string { return __('resources.transfer.plural'); }
    public static function getGloballySearchableAttributes(): array { return ['reference', 'external_ref', 'customer.name', 'customer.phone']; }

    protected static function nameColumn(): string
    {
        return app()->getLocale() === 'en' ? 'name_en' : 'name_ar';
    }

    public static function form(Schema $schema): Schema
    {
        $currency = fn (string $field, string $label) => Select::make($field)->label(__($label))
            ->relationship(\Illuminate\Support\Str::camel(str_replace('_id', '', $field)), 'code')->searchable()->preload()->native(false);

        return $schema->components([
            Section::make(__('fields.basic'))->columns(3)->columnSpanFull()->disabled(fn (?Transfer $record): bool => (bool) $record?->isFinanciallyLocked())->schema([
                Placeholder::make('reference_display')->label(__('fields.reference'))
                    ->content(fn (?Transfer $record) => $record?->reference ?? __('fields.auto_generated')),
                Placeholder::make('status_display')->label(__('fields.status'))
                    ->content(fn (?Transfer $record) => ($record?->status ?? TransferStatus::New)->getLabel()),
                Select::make('assigned_user_id')->label(__('fields.assigned_user'))->relationship('assignedUser', 'name')->searchable()->preload()->nullable(),
                Select::make('customer_id')->label(__('fields.customer'))->required()->searchable(['name', 'phone', 'code'])->preload()
                    ->relationship('customer', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Customer $c) => "{$c->code} — {$c->name}" . ($c->phone ? " ({$c->phone})" : '')),
                Select::make('type')->label(__('fields.type'))->options(TransferType::class)->required()->native(false),
                Select::make('service_id')->label(__('fields.service'))->relationship('service', static::nameColumn())->searchable()->preload()->live()->nullable(),
                Select::make('country_id')->label(__('fields.country'))->relationship('country', static::nameColumn())->searchable()->preload()->nullable(),
            ]),

            Section::make(__('fields.amounts'))->columns(4)->columnSpanFull()->disabled(fn (?Transfer $record): bool => (bool) $record?->isFinanciallyLocked())->schema([
                TextInput::make('send_amount')->label(__('fields.send_amount'))->numeric()->inputMode('decimal')->minValue(0),
                $currency('send_currency_id', 'fields.send_currency'),
                TextInput::make('rate')->label(__('fields.rate'))->numeric()->inputMode('decimal')->minValue(0),
                TextInput::make('commission_amount')->label(__('fields.commission_amount'))->numeric()->inputMode('decimal')->minValue(0),
                TextInput::make('deliver_amount')->label(__('fields.deliver_amount'))->numeric()->inputMode('decimal')->minValue(0),
                $currency('deliver_currency_id', 'fields.deliver_currency'),
                TextInput::make('fee_amount')->label(__('fields.fee_amount'))->numeric()->inputMode('decimal')->minValue(0),
                $currency('fee_currency_id', 'fields.fee_currency'),
                Select::make('delivery_method_id')->label(__('fields.delivery_method'))->relationship('deliveryMethod', static::nameColumn())->searchable()->preload()->nullable()->columnSpan(2),
                Select::make('payment_account_id')->label(__('fields.payment_account'))->searchable()->nullable()->columnSpan(2)
                    ->options(fn (Get $get) => PaymentAccount::active()
                        ->when($get('service_id'), fn ($q, $s) => $q->where('service_id', $s))
                        ->orderBy('internal_name')->pluck('internal_name', 'id')->all()),
            ]),

            Section::make(__('fields.parties'))->columns(2)->columnSpanFull()->disabled(fn (?Transfer $record): bool => (bool) $record?->isFinanciallyLocked())->schema([
                TextInput::make('sender_name')->label(__('fields.sender_name'))->maxLength(150),
                TextInput::make('beneficiary_name')->label(__('fields.beneficiary_name'))->maxLength(150),
                TextInput::make('beneficiary_details.bank_name')->label(__('fields.d_bank_name')),
                TextInput::make('beneficiary_details.account')->label(__('fields.d_bank_account')),
                TextInput::make('beneficiary_details.phone')->label(__('fields.d_phone')),
                TextInput::make('beneficiary_details.wallet')->label(__('fields.d_address')),
                TextInput::make('external_ref')->label(__('fields.external_ref'))->maxLength(150)->columnSpanFull(),
            ]),

            Section::make(__('fields.notes_dates'))->columns(2)->columnSpanFull()->schema([
                DateTimePicker::make('scheduled_at')->label(__('fields.scheduled_at'))->seconds(false),
                Textarea::make('internal_notes')->label(__('fields.internal_notes'))->rows(3)->columnSpanFull(),
                FileUpload::make('delivery_proof_file')->label(__('fields.delivery_proof'))->disk('local')->directory('proofs')->visibility('private')
                    ->disabled(fn (?Transfer $record): bool => (bool) $record?->status->isTerminal())
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])->maxSize(8192)->openable()->downloadable()->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $money = fn ($state, ?string $code) => $state === null ? '—' : rtrim(rtrim(number_format((float) $state, 8, '.', ','), '0'), '.') . ' ' . ($code ?? '');

        return $table
            ->columns([
                TextColumn::make('reference')->label(__('fields.reference'))->searchable()->sortable()->weight('bold')->fontFamily('mono')->copyable(),
                TextColumn::make('customer.name')->label(__('fields.customer'))->searchable()->sortable(),
                TextColumn::make('type')->label(__('fields.type'))->badge()->color('gray'),
                TextColumn::make('service.' . static::nameColumn())->label(__('fields.service'))->placeholder('—')->toggleable(),
                TextColumn::make('send_amount')->label(__('fields.send_amount'))->formatStateUsing(fn ($state, Transfer $r) => $money($state, $r->sendCurrency?->code))->sortable(),
                TextColumn::make('deliver_amount')->label(__('fields.deliver_amount'))->formatStateUsing(fn ($state, Transfer $r) => $money($state, $r->deliverCurrency?->code))->sortable(),
                TextColumn::make('status')->label(__('fields.status'))->badge(),
                TextColumn::make('assignedUser.name')->label(__('fields.assigned_user'))->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label(__('fields.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('fields.status'))->options(TransferStatus::class)->multiple(),
                SelectFilter::make('type')->label(__('fields.type'))->options(TransferType::class),
                SelectFilter::make('service_id')->label(__('fields.service'))->relationship('service', static::nameColumn())->preload(),
                SelectFilter::make('assigned_user_id')->label(__('fields.assigned_user'))->relationship('assignedUser', 'name')->preload(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->paginated([25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [ReceiptsRelationManager::class, StatusLogsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransfers::route('/'),
            'create' => CreateTransfer::route('/create'),
            'view' => ViewTransfer::route('/{record}'),
            'edit' => EditTransfer::route('/{record}/edit'),
        ];
    }
}
