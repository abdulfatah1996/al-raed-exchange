<?php

namespace App\Filament\Resources\Announcements;

use App\Enums\AnnouncementType;
use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Models\Announcement;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    public static function getNavigationGroup(): string { return __('resources.groups.operations'); }
    public static function getNavigationIcon(): string { return 'heroicon-o-megaphone'; }
    public static function getNavigationSort(): int { return 60; }
    public static function getModelLabel(): string { return __('web.admin.announcement'); }
    public static function getPluralModelLabel(): string { return __('web.admin.announcements'); }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('type')->label(__('web.admin.type'))->options(AnnouncementType::class)->default(AnnouncementType::Info)->required()->native(false),
            DateTimePicker::make('expires_at')->label(__('web.admin.expires_at'))->seconds(false),
            TextInput::make('title_ar')->label(__('web.admin.title_ar'))->required()->maxLength(200),
            TextInput::make('title_en')->label(__('web.admin.title_en'))->maxLength(200),
            Textarea::make('body_ar')->label(__('web.admin.body_ar'))->required()->rows(4)->maxLength(3000),
            Textarea::make('body_en')->label(__('web.admin.body_en'))->rows(4)->maxLength(3000),
            Toggle::make('is_published')->label(__('web.admin.published')),
            Toggle::make('is_pinned')->label(__('web.admin.pinned')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_ar')->label(__('web.admin.title_ar'))->searchable()->limit(60)->weight('bold'),
                TextColumn::make('type')->label(__('web.admin.type'))->badge(),
                IconColumn::make('is_published')->label(__('web.admin.published'))->boolean(),
                IconColumn::make('is_pinned')->label(__('web.admin.pinned'))->boolean(),
                TextColumn::make('published_at')->label(__('web.admin.published_at'))->dateTime('Y-m-d H:i')->sortable()->placeholder('—'),
                TextColumn::make('expires_at')->label(__('web.admin.expires_at'))->dateTime('Y-m-d H:i')->placeholder('—')->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAnnouncements::route('/')];
    }
}
