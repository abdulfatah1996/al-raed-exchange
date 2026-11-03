<?php

namespace App\Filament\Resources\DeliveryMethods;

use App\Filament\Resources\DeliveryMethods\Pages\ManageDeliveryMethods;
use App\Filament\Resources\ReferenceResource;
use App\Models\DeliveryMethod;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DeliveryMethodResource extends ReferenceResource
{
    protected static ?string $model = DeliveryMethod::class;

    protected static string $labelKey = 'delivery_method';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationSort(): int
    {
        return 14;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('fields.code'))->required()->maxLength(50)->unique(ignoreRecord: true)->alphaDash(),
            Select::make('currency_id')->label(__('fields.currency'))->relationship('currency', 'code')->searchable()->preload()->nullable(),
            TextInput::make('name_ar')->label(__('fields.name_ar'))->required()->maxLength(100),
            TextInput::make('name_en')->label(__('fields.name_en'))->required()->maxLength(100),
            TextInput::make('sort')->label(__('fields.sort'))->numeric()->default(0),
            Toggle::make('is_active')->label(__('fields.is_active'))->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('fields.code'))->searchable()->sortable()->weight('bold'),
                TextColumn::make('name_ar')->label(__('fields.name_ar'))->searchable()->sortable(),
                TextColumn::make('name_en')->label(__('fields.name_en'))->searchable()->sortable()->toggleable(),
                TextColumn::make('currency.code')->label(__('fields.currency'))->placeholder('—')->badge(),
                ToggleColumn::make('is_active')->label(__('fields.is_active')),
            ])
            ->defaultSort('sort')
            ->filters([TernaryFilter::make('is_active')->label(__('fields.is_active'))])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDeliveryMethods::route('/')];
    }
}
