<?php

namespace App\Filament\Resources\Customers;

use Filament\Actions\ActionGroup;
use App\Filament\Tables\ExpandableRow;
use App\Enums\CustomerStatus;
use App\Enums\KycStatus;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\RelationManagers\NotesRelationManager;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return __('resources.groups.operations');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationSort(): int
    {
        return 30;
    }

    public static function getModelLabel(): string
    {
        return __('resources.customer.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.customer.plural');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'name', 'phone', 'whatsapp'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [__('fields.customer_code') => $record->code, __('fields.phone') => $record->phone];
    }

    protected static function nameColumn(): string
    {
        return app()->getLocale() === 'en' ? 'name_en' : 'name_ar';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('fields.basic'))->columns(2)->columnSpanFull()->schema([
                TextInput::make('code')->label(__('fields.customer_code'))->disabled()->dehydrated(false)->placeholder('يُولَّد تلقائيًا'),
                TextInput::make('name')->label(__('fields.full_name'))->required()->maxLength(150),
                TextInput::make('phone')->label(__('fields.phone'))->tel()->maxLength(30)->placeholder('+970597000000'),
                TextInput::make('whatsapp')->label(__('fields.whatsapp'))->tel()->maxLength(30)->helperText('يُترك فارغًا إن كان نفس رقم الهاتف'),
                Select::make('country_id')->label(__('fields.country'))->relationship('country', static::nameColumn())->searchable()->preload()->nullable(),
                Select::make('status')->label(__('fields.status'))->options(CustomerStatus::class)->default('active')->required()->native(false),
                Select::make('kyc_status')->label(__('fields.kyc_status'))->options(KycStatus::class)->default('none')->required()->native(false),
                Textarea::make('notes')->label(__('fields.notes'))->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['country', 'creator']))
            ->columns(ExpandableRow::columns([
                ExpandableRow::labelled(TextColumn::make('code')->label(__('fields.customer_code'))->searchable()->sortable()->weight('bold')->fontFamily('mono')),
                ExpandableRow::labelled(TextColumn::make('name')->label(__('fields.full_name'))->searchable()->sortable()->wrap()),
                ExpandableRow::labelled(TextColumn::make('phone')->label(__('fields.phone'))->searchable()
                    ->formatStateUsing(fn ($state) => ExpandableRow::ltr($state))),
                ExpandableRow::labelled(TextColumn::make('status')->label(__('fields.status'))->badge()),
                ExpandableRow::labelled(TextColumn::make('kyc_status')->label(__('fields.kyc_status'))->badge()),
            ], fn (Customer $r) => [
                __('fields.whatsapp') => ExpandableRow::code($r->whatsapp),
                __('fields.country') => $r->country?->{static::nameColumn()},
                __('fields.created_at') => $r->created_at,
                __('fields.created_by') => $r->creator?->name,
                __('fields.notes') => ExpandableRow::wide($r->notes),
            ]))
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('fields.status'))->options(CustomerStatus::class),
                SelectFilter::make('kyc_status')->label(__('fields.kyc_status'))->options(KycStatus::class),
                SelectFilter::make('country_id')->label(__('fields.country'))->relationship('country', static::nameColumn())->preload(),
                TrashedFilter::make(),
            ])
            ->recordUrl(null)->recordAction(null)
            ->recordActions([ActionGroup::make([
                EditAction::make(),
                Action::make('whatsapp')->label('WhatsApp')->icon('heroicon-o-chat-bubble-left-right')->color('success')
                    ->url(fn (Customer $r) => $r->whatsapp_url, shouldOpenInNewTab: true)->visible(fn (Customer $r) => (bool) $r->whatsapp_url),
            ])->tooltip(__('admin.common.actions'))])
            ->paginated([25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [NotesRelationManager::class];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
