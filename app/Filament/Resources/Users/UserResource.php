<?php

namespace App\Filament\Resources\Users;

use Filament\Actions\ActionGroup;
use App\Filament\Tables\ExpandableRow;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string { return __('resources.groups.system'); }
    public static function getNavigationIcon(): string { return 'heroicon-o-users'; }
    public static function getNavigationSort(): int { return 90; }
    public static function getModelLabel(): string { return __('admin.users.singular'); }
    public static function getPluralModelLabel(): string { return __('admin.users.plural'); }

    /** @return array<string, string> role name => translated label */
    public static function roleOptions(): array
    {
        return collect(array_keys(Permissions::roles()))->mapWithKeys(fn (string $r) => [$r => __("dashboard.roles.$r")])->all();
    }

    public static function form(Schema $schema): Schema
    {
        $isSelf = fn (?User $record): bool => $record !== null && $record->is(auth()->user());

        return $schema->components([
            Section::make(__('admin.users.section_account'))->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label(__('admin.users.name'))->required()->maxLength(255),
                TextInput::make('email')->label(__('admin.users.email'))->email()->required()->maxLength(255)
                    ->unique(ignoreRecord: true)->dehydrateStateUsing(fn (?string $state) => mb_strtolower(trim((string) $state))),
                TextInput::make('password')->label(fn (string $operation) => $operation === 'create' ? __('admin.users.password') : __('admin.users.password_new'))
                    ->password()->revealable()->autocomplete('new-password')
                    ->required(fn (string $operation) => $operation === 'create')
                    ->rule(Password::min(12)->letters()->numbers())
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? __('admin.users.password_hint') : null),
                TextInput::make('password_confirmation')->label(__('admin.users.password_confirm'))
                    ->password()->revealable()->autocomplete('new-password')
                    ->required(fn (string $operation, $get) => $operation === 'create' || filled($get('password')))
                    ->same('password')->dehydrated(false),
            ]),
            Section::make(__('admin.users.section_access'))->columns(3)->columnSpanFull()
                ->description(fn (?User $record) => $isSelf($record) ? __('admin.users.self_locked') : null)
                ->schema([
                    Select::make('role')->label(__('admin.users.role'))->options(static::roleOptions())->required()->native(false)
                        ->disabled($isSelf)
                        ->helperText(fn (?string $state) => $state ? __("admin.roles.descriptions.$state") : null)->live(),
                    Select::make('locale')->label(__('admin.users.locale'))->options(collect(config('locales.supported'))->map(fn ($l) => $l['name'])->all())
                        ->default(config('locales.default'))->native(false),
                    Toggle::make('is_active')->label(__('admin.users.active'))->default(true)->disabled($isSelf)->inline(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('roles'))
            ->columns(ExpandableRow::columns([
                ExpandableRow::labelled(TextColumn::make('name')->label(__('admin.users.name'))->searchable()->sortable()->weight('bold')->wrap()),
                ExpandableRow::labelled(TextColumn::make('roles.name')->label(__('admin.users.role'))->badge()->color('primary')
                    ->formatStateUsing(fn (string $state) => __("dashboard.roles.$state"))),
                ExpandableRow::labelled(ExpandableRow::yesNo('is_active')->label(__('admin.users.active'))),
                ExpandableRow::labelled(ExpandableRow::yesNo('mfa')->label(__('admin.users.mfa'))
                    ->state(fn (User $u) => $u->hasAppAuthentication())),
            ], fn (User $u) => [
                __('admin.users.email') => ExpandableRow::code($u->email),
                __('admin.users.locale') => $u->locale ? strtoupper($u->locale) : null,
                __('admin.users.created_at') => $u->created_at,
                __('fields.updated_at') => $u->updated_at,
            ]))
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('role')->label(__('admin.users.role'))->options(static::roleOptions())
                    ->query(fn ($query, array $data) => $data['value'] ? $query->role($data['value']) : $query),
                TernaryFilter::make('is_active')->label(__('admin.users.active')),
            ])
            ->recordUrl(null)->recordAction(null)
            ->recordActions([ActionGroup::make([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (User $u) => $u->is_active ? __('admin.users.deactivate') : __('admin.users.activate'))
                    ->icon(fn (User $u) => $u->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (User $u) => $u->is_active ? 'danger' : 'success')
                    ->authorize('toggleActive')->requiresConfirmation()
                    ->action(function (User $u) {
                        try {
                            $u->update(['is_active' => ! $u->is_active]);
                            Notification::make()->title($u->is_active ? __('admin.users.activated') : __('admin.users.deactivated'))->success()->send();
                        } catch (RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('resetMfa')->label(__('admin.users.reset_mfa'))->icon('heroicon-o-key')->color('warning')
                    ->authorize('resetMfa')->requiresConfirmation()->modalDescription(__('admin.users.reset_mfa_help'))
                    ->action(function (User $u) {
                        $u->saveAppAuthenticationSecret(null);
                        $u->saveAppAuthenticationRecoveryCodes(null);
                        Notification::make()->title(__('admin.users.mfa_reset_done'))->success()->send();
                    }),
            ])->tooltip(__('admin.common.actions'))]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
