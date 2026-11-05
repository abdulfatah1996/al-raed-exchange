<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Support\Permissions;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

/** Fixed set of roles; edit which permissions each non-admin role carries. */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    public static function getNavigationGroup(): string { return __('resources.groups.system'); }
    public static function getNavigationIcon(): string { return 'heroicon-o-shield-check'; }
    public static function getNavigationSort(): int { return 91; }
    public static function getModelLabel(): string { return __('admin.roles.singular'); }
    public static function getPluralModelLabel(): string { return __('admin.roles.plural'); }
    public static function getNavigationLabel(): string { return __('admin.roles.plural'); }

    public static function getEloquentQuery(): Builder
    {
        $order = array_keys(Permissions::roles());

        return parent::getEloquentQuery()
            ->whereIn('name', $order)
            ->withCount(['users', 'permissions'])
            ->orderByRaw('CASE name ' . collect($order)->map(fn ($r, $i) => "WHEN '{$r}' THEN {$i}")->join(' ') . ' END');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(
            collect(Permissions::grouped())->map(fn (array $permissions, string $group) => Section::make(__("admin.permission_groups.$group"))
                ->schema([
                    CheckboxList::make("permissions_{$group}")->hiddenLabel()
                        ->options(collect($permissions)->mapWithKeys(fn ($p) => [$p => Permissions::label($p)])->all())
                        ->bulkToggleable(count($permissions) > 2),
                ])->compact()->columnSpan(1)
            )->values()->all()
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('admin.roles.role'))->weight('bold')
                    ->formatStateUsing(fn (string $state) => __("dashboard.roles.$state"))
                    ->description(fn (Role $r) => __("admin.roles.descriptions.{$r->name}")),
                TextColumn::make('users_count')->label(__('admin.roles.users_count'))->badge()->color('gray'),
                TextColumn::make('permissions_count')->label(__('admin.roles.permissions_count'))
                    ->formatStateUsing(fn ($state) => $state . ' / ' . count(Permissions::all())),
            ])
            ->paginated(false)
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
