<?php

namespace App\Filament\Resources\Currencies;

use App\Enums\CurrencyType;
use App\Filament\Resources\Currencies\Pages\ManageCurrencies;
use App\Filament\Resources\ReferenceResource;
use App\Models\Currency;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CurrencyResource extends ReferenceResource
{
    protected static ?string $model = Currency::class;

    protected static string $labelKey = 'currency';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationSort(): int
    {
        return 11;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('fields.code'))->required()->maxLength(10)->unique(ignoreRecord: true)->placeholder('USD'),
            Select::make('type')->label(__('fields.type'))->options(CurrencyType::class)->required()->default('fiat')->native(false),
            TextInput::make('name_ar')->label(__('fields.name_ar'))->required()->maxLength(100),
            TextInput::make('name_en')->label(__('fields.name_en'))->required()->maxLength(100),
            TextInput::make('symbol')->label(__('fields.symbol'))->maxLength(8),
            TextInput::make('decimals')->label(__('fields.decimals'))->numeric()->minValue(0)->maxValue(8)->default(2)->required(),
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
                TextColumn::make('name_en')->label(__('fields.name_en'))->searchable()->sortable(),
                TextColumn::make('symbol')->label(__('fields.symbol')),
                TextColumn::make('decimals')->label(__('fields.decimals'))->alignCenter(),
                TextColumn::make('type')->label(__('fields.type'))->badge(),
                ToggleColumn::make('is_active')->label(__('fields.is_active')),
            ])
            ->defaultSort('sort')
            ->filters([
                SelectFilter::make('type')->label(__('fields.type'))->options(CurrencyType::class),
                TernaryFilter::make('is_active')->label(__('fields.is_active')),
            ])
            ->recordActions([EditAction::make()])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCurrencies::route('/')];
    }
}
