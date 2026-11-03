<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Resources\Countries\Pages\ManageCountries;
use App\Filament\Resources\ReferenceResource;
use App\Models\Country;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CountryResource extends ReferenceResource
{
    protected static ?string $model = Country::class;

    protected static string $labelKey = 'country';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-globe-alt';
    }

    public static function getNavigationSort(): int
    {
        return 10;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('fields.code'))->required()->maxLength(2)->unique(ignoreRecord: true)
                ->helperText('ISO 3166-1 alpha-2 (PS, JO, EG...)'),
            TextInput::make('name_ar')->label(__('fields.name_ar'))->required()->maxLength(100),
            TextInput::make('name_en')->label(__('fields.name_en'))->required()->maxLength(100),
            TextInput::make('dial_code')->label(__('fields.dial_code'))->maxLength(8)->placeholder('+970'),
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
                TextColumn::make('dial_code')->label(__('fields.dial_code')),
                ToggleColumn::make('is_active')->label(__('fields.is_active')),
                TextColumn::make('sort')->label(__('fields.sort'))->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort')
            ->filters([TernaryFilter::make('is_active')->label(__('fields.is_active'))])
            ->recordActions([EditAction::make()])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCountries::route('/')];
    }
}
