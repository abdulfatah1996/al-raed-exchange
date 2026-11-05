<?php

namespace App\Filament\Resources\PaymentAccounts;

use Filament\Actions\ActionGroup;
use App\Filament\Tables\ExpandableRow;
use App\Enums\PaymentAccountStatus;
use App\Enums\PaymentAccountType;
use App\Filament\Resources\PaymentAccounts\Pages\ManagePaymentAccounts;
use App\Filament\Resources\ReferenceResource;
use App\Models\CryptoNetwork;
use App\Models\PaymentAccount;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentAccountResource extends ReferenceResource
{
    protected static ?string $model = PaymentAccount::class;

    protected static string $labelKey = 'payment_account';

    public static function getNavigationGroup(): string
    {
        return __('resources.groups.accounts');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-credit-card';
    }

    public static function getNavigationSort(): int
    {
        return 20;
    }

    /** Current "type" value as a plain string (the form state may hold the enum instance). */
    protected static function typeOf(Get $get): ?string
    {
        $type = $get('type');

        return $type instanceof \BackedEnum ? $type->value : $type;
    }

    public static function form(Schema $schema): Schema
    {
        $is = fn (string ...$types) => fn (Get $get): bool => in_array(static::typeOf($get), $types, true);

        return $schema->components([
            Section::make(__('fields.basic'))->columns(2)->columnSpanFull()->schema([
                Select::make('type')->label(__('fields.type'))->options(PaymentAccountType::class)->required()->live()->native(false),
                TextInput::make('internal_name')->label(__('fields.internal_name'))->required()->maxLength(100)->placeholder('PayPal #1 — أحمد'),
                Select::make('service_id')->label(__('fields.service'))->relationship('service', static::nameColumn())->searchable()->preload()->nullable(),
                Select::make('country_id')->label(__('fields.country'))->relationship('country', static::nameColumn())->searchable()->preload()->nullable(),
                Select::make('currency_id')->label(__('fields.currency'))->relationship('currency', 'code')->searchable()->preload()->nullable(),
                TextInput::make('beneficiary_name')->label(__('fields.beneficiary_name'))->maxLength(150),
                Select::make('status')->label(__('fields.status'))->options(PaymentAccountStatus::class)->default('active')->required()->native(false),
            ]),

            Section::make(__('fields.receiving_details'))->columns(2)->columnSpanFull()
                ->description(fn (Get $get): ?string => static::typeOf($get) ? null : __('fields.choose_type_first'))
                ->schema([
                    TextInput::make('details.email')->label(__('fields.d_email'))->email()->visible($is('paypal')),
                    TextInput::make('details.paypal_me')->label(__('fields.d_paypal_me'))->url()->visible($is('paypal')),

                    TextInput::make('details.bank_name')->label(__('fields.d_bank_name'))->visible($is('bank')),
                    TextInput::make('details.account_number')->label(__('fields.d_account_number'))->visible($is('bank')),
                    TextInput::make('details.iban')->label(__('fields.d_iban'))->visible($is('bank')),
                    TextInput::make('details.swift')->label(__('fields.d_swift'))->visible($is('bank')),
                    TextInput::make('details.reference')->label(__('fields.d_reference'))->visible($is('bank')),

                    Select::make('details.network')->label(__('fields.d_network'))->visible($is('crypto_wallet'))->native(false)
                        ->options(fn () => CryptoNetwork::active()->orderBy('sort')->pluck('name', 'code')->all()),
                    TextInput::make('details.address')->label(__('fields.d_address'))->visible($is('crypto_wallet', 'instapay'))->columnSpanFull(),

                    TextInput::make('details.phone')->label(__('fields.d_phone'))->tel()->visible($is('ewallet', 'cliq', 'instapay', 'vodafone_cash')),
                    TextInput::make('details.alias')->label(__('fields.d_alias'))->visible($is('cliq')),
                    TextInput::make('details.wallet_id')->label(__('fields.d_wallet_id'))->visible($is('ewallet')),

                    Textarea::make('details.extra')->label(__('fields.d_extra'))->rows(3)->visible($is('other'))->columnSpanFull(),
                ]),

            Section::make(__('fields.limits'))->columns(3)->columnSpanFull()->schema([
                TextInput::make('min_amount')->label(__('fields.min_amount'))->numeric()->minValue(0),
                TextInput::make('max_amount')->label(__('fields.max_amount'))->numeric()->minValue(0),
                TextInput::make('daily_cap')->label(__('fields.daily_cap'))->numeric()->minValue(0),
                Textarea::make('instructions_ar')->label(__('fields.instructions_ar'))->rows(3)->columnSpanFull(),
                Textarea::make('instructions_en')->label(__('fields.instructions_en'))->rows(3)->columnSpanFull(),
                Textarea::make('notes')->label(__('fields.notes'))->rows(2)->columnSpan(2),
                DateTimePicker::make('last_verified_at')->label(__('fields.last_verified_at'))->seconds(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['service', 'country', 'currency', 'creator']))
            ->columns(ExpandableRow::columns([
                ExpandableRow::labelled(TextColumn::make('internal_name')->label(__('fields.internal_name'))->searchable()->sortable()->weight('bold')->wrap()),
                ExpandableRow::labelled(TextColumn::make('type')->label(__('fields.type'))->badge()->color('info')),
                ExpandableRow::labelled(TextColumn::make('currency.code')->label(__('fields.currency'))->placeholder('—')),
                ExpandableRow::labelled(TextColumn::make('masked_summary')->label(__('fields.masked_summary'))->fontFamily('mono')
                    ->formatStateUsing(fn ($state) => ExpandableRow::ltr($state))),
                ExpandableRow::labelled(TextColumn::make('status')->label(__('fields.status'))->badge()),
            ], fn (PaymentAccount $r) => [
                __('fields.service') => $r->service?->{static::nameColumn()},
                __('fields.country') => $r->country?->{static::nameColumn()},
                __('fields.beneficiary_name') => $r->beneficiary_name,
                __('fields.min_amount') => ExpandableRow::money($r->min_amount, $r->currency?->code),
                __('fields.max_amount') => ExpandableRow::money($r->max_amount, $r->currency?->code),
                __('fields.daily_cap') => ExpandableRow::money($r->daily_cap, $r->currency?->code),
                __('fields.last_verified_at') => $r->last_verified_at,
                __('fields.created_by') => $r->creator?->name,
                __('fields.notes') => ExpandableRow::wide($r->notes),
            ]))
            ->defaultSort('status')
            ->filters([
                SelectFilter::make('type')->label(__('fields.type'))->options(PaymentAccountType::class),
                SelectFilter::make('status')->label(__('fields.status'))->options(PaymentAccountStatus::class),
                SelectFilter::make('country_id')->label(__('fields.country'))->relationship('country', static::nameColumn())->preload(),
            ])
            ->recordUrl(null)->recordAction(null)
            ->recordActions([ActionGroup::make([
                EditAction::make()->modalWidth('4xl'),
                Action::make('pause')->label(__('enums.payment_account_status.paused'))->icon('heroicon-o-pause-circle')->color('warning')
                    ->visible(fn (PaymentAccount $r) => $r->status === PaymentAccountStatus::Active)
                    ->requiresConfirmation()->action(fn (PaymentAccount $r) => $r->update(['status' => PaymentAccountStatus::Paused])),
                Action::make('activate')->label(__('enums.payment_account_status.active'))->icon('heroicon-o-play-circle')->color('success')
                    ->visible(fn (PaymentAccount $r) => $r->status === PaymentAccountStatus::Paused)
                    ->action(fn (PaymentAccount $r) => $r->update(['status' => PaymentAccountStatus::Active])),
                Action::make('retire')->label(__('enums.payment_account_status.retired'))->icon('heroicon-o-archive-box-x-mark')->color('gray')
                    ->visible(fn (PaymentAccount $r) => $r->status !== PaymentAccountStatus::Retired)
                    ->requiresConfirmation()->action(fn (PaymentAccount $r) => $r->update(['status' => PaymentAccountStatus::Retired])),
            ])->tooltip(__('admin.common.actions'))])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePaymentAccounts::route('/')];
    }
}
