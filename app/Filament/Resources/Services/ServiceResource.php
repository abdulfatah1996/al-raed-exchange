<?php

namespace App\Filament\Resources\Services;

use App\Enums\ServiceDirection;
use App\Filament\Resources\ReferenceResource;
use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Service;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServiceResource extends ReferenceResource
{
    protected static ?string $model = Service::class;

    protected static string $labelKey = 'service';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public static function getNavigationSort(): int
    {
        return 13;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('fields.code'))->required()->maxLength(50)->unique(ignoreRecord: true)
                ->placeholder('jordan_cliq')->helperText('حروف إنجليزية صغيرة وشرطة سفلية فقط')->alphaDash(),
            Select::make('direction')->label(__('fields.direction'))->options(ServiceDirection::class)->required()->default('both')->native(false),
            TextInput::make('name_ar')->label(__('fields.name_ar'))->required()->maxLength(100),
            TextInput::make('name_en')->label(__('fields.name_en'))->required()->maxLength(100),
            Select::make('country_id')->label(__('fields.country'))->relationship('country', static::nameColumn())->searchable()->preload()->nullable(),
            TextInput::make('sort')->label(__('fields.sort'))->numeric()->default(0),
            Textarea::make('description_ar')->label(__('fields.description_ar'))->rows(2)->columnSpanFull(),
            Textarea::make('description_en')->label(__('fields.description_en'))->rows(2)->columnSpanFull(),
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
                TextColumn::make('country.' . static::nameColumn())->label(__('fields.country'))->placeholder('—')->sortable(),
                TextColumn::make('direction')->label(__('fields.direction'))->badge(),
                ToggleColumn::make('is_active')->label(__('fields.is_active')),
            ])
            ->defaultSort('sort')
            ->filters([
                SelectFilter::make('country_id')->label(__('fields.country'))->relationship('country', static::nameColumn())->preload(),
                SelectFilter::make('direction')->label(__('fields.direction'))->options(ServiceDirection::class),
                TernaryFilter::make('is_active')->label(__('fields.is_active')),
            ])
            ->recordActions([EditAction::make()])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageServices::route('/')];
    }
}
