<?php

namespace App\Filament\Resources\CryptoNetworks;

use App\Filament\Resources\CryptoNetworks\Pages\ManageCryptoNetworks;
use App\Filament\Resources\ReferenceResource;
use App\Models\CryptoNetwork;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CryptoNetworkResource extends ReferenceResource
{
    protected static ?string $model = CryptoNetwork::class;

    protected static string $labelKey = 'crypto_network';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cube-transparent';
    }

    public static function getNavigationSort(): int
    {
        return 12;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('fields.code'))->required()->maxLength(20)->unique(ignoreRecord: true)->placeholder('TRC20'),
            TextInput::make('name')->label(__('fields.name'))->required()->maxLength(100),
            TextInput::make('sort')->label(__('fields.sort'))->numeric()->default(0),
            Toggle::make('is_active')->label(__('fields.is_active'))->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('fields.code'))->searchable()->sortable()->weight('bold'),
                TextColumn::make('name')->label(__('fields.name'))->searchable()->sortable(),
                ToggleColumn::make('is_active')->label(__('fields.is_active')),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCryptoNetworks::route('/')];
    }
}
