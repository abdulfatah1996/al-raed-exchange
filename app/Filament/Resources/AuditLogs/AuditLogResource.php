<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Tables\ExpandableRow;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    public static function getNavigationGroup(): string { return __('resources.groups.system'); }
    public static function getNavigationIcon(): string { return 'heroicon-o-finger-print'; }
    public static function getNavigationSort(): int { return 95; }
    public static function getModelLabel(): string { return __('admin.audit.singular'); }
    public static function getPluralModelLabel(): string { return __('admin.audit.plural'); }

    /** @return array<string, string> morph class => label */
    public static function subjectTypes(): array
    {
        return collect(__('admin.audit.types'))->mapWithKeys(fn ($label, $class) => [str_replace('/', '\\', $class) => $label])->all();
    }

    public static function table(Table $table): Table
    {
        $events = __('admin.audit.events');
        $types = static::subjectTypes();

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns(ExpandableRow::columns([
                ExpandableRow::labelled(ExpandableRow::dateTime('created_at')->label(__('admin.audit.when'))->sortable()),
                ExpandableRow::labelled(TextColumn::make('user.name')->label(__('admin.audit.who'))->placeholder(__('admin.audit.system'))->searchable()),
                ExpandableRow::labelled(TextColumn::make('event')->label(__('admin.audit.event'))->badge()
                    ->formatStateUsing(fn (string $state) => $events[$state] ?? $state)
                    ->color(fn (string $state) => ['created' => 'success', 'deleted' => 'danger', 'updated' => 'info'][$state] ?? 'warning')),
                ExpandableRow::labelled(TextColumn::make('auditable_type')->label(__('admin.audit.subject_type'))
                    ->formatStateUsing(fn (string $state) => $types[$state] ?? class_basename($state))),
                ExpandableRow::labelled(TextColumn::make('subject_label')->label(__('admin.audit.subject'))->searchable()->fontFamily('mono')->limit(32)),
            ], function (AuditLog $log) {
                $rows = [
                    __('admin.audit.ip') => ExpandableRow::code($log->ip_address),
                    __('admin.audit.record_id') => (string) $log->auditable_id,
                ];
                $old = $log->old_values ?? [];
                $new = $log->new_values ?? [];
                foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $field) {
                    $label = match (true) {
                        __("admin.audit.fields.$field") !== "admin.audit.fields.$field" => __("admin.audit.fields.$field"),
                        __("fields.$field") !== "fields.$field" => __("fields.$field"),
                        default => $field,
                    };
                    $rows[$label] = [
                        'value' => static::fmt($new[$field] ?? null),
                        'before' => array_key_exists($field, $old) ? static::fmt($old[$field]) : null,
                        'wide' => true,
                    ];
                }

                return $rows;
            }))
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('event')->label(__('admin.audit.event'))->options($events),
                SelectFilter::make('auditable_type')->label(__('admin.audit.subject_type'))->options($types),
                SelectFilter::make('user_id')->label(__('admin.audit.who'))->relationship('user', 'name')->preload(),
            ])
            ->recordUrl(null)->recordAction(null)
            ->paginated([25, 50, 100]);
    }

    private static function fmt(mixed $v): string
    {
        return match (true) {
            $v === null || $v === '' => '—',
            is_array($v) => implode('، ', array_map(fn ($x) => is_scalar($x) ? (string) $x : json_encode($x, JSON_UNESCAPED_UNICODE), $v)) ?: '—',
            is_bool($v) => $v ? '1' : '0',
            default => (string) $v,
        };
    }

    public static function canCreate(): bool { return false; }

    public static function getPages(): array
    {
        return ['index' => ListAuditLogs::route('/')];
    }
}
