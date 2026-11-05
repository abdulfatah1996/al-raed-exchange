<?php

namespace App\Filament\Resources\Transfers\RelationManagers;

use App\Filament\Tables\ExpandableRow;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StatusLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'statusLogs';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('fields.status_log');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_status')->label(__('fields.from_status'))->badge()->placeholder('—'),
                TextColumn::make('to_status')->label(__('fields.to_status'))->badge(),
                TextColumn::make('user.name')->label(__('fields.user'))->placeholder('—'),
                TextColumn::make('note')->label(__('fields.note'))->wrap()->placeholder('—'),
                ExpandableRow::dateTime('created_at')->label(__('fields.created_at')),
            ])
            ->defaultSort('id', 'desc')
            ->paginated(false);
    }
}
