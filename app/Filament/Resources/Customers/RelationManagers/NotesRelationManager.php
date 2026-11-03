<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notesLog';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('fields.notes_log');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('note')->label(__('fields.note'))->required()->rows(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('note')->label(__('fields.note'))->wrap(),
                TextColumn::make('user.name')->label(__('fields.user'))->placeholder('—'),
                TextColumn::make('created_at')->label(__('fields.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([CreateAction::make()->label(__('fields.note'))]);
    }
}
