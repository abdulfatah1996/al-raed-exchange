<?php

namespace App\Filament\Resources\Announcements;

use App\Enums\AnnouncementType;
use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Models\Announcement;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
        return $schema->components([
            Section::make(__('admin.announcements.section_content'))->columns(2)->columnSpanFull()->schema([
                Select::make('type')->label(__('web.admin.type'))->options(AnnouncementType::class)->default(AnnouncementType::Info)->required()->native(false)->columnSpanFull(),
                TextInput::make('title_ar')->label(__('web.admin.title_ar'))->required()->maxLength(200),
                TextInput::make('title_en')->label(__('web.admin.title_en'))->maxLength(200),
                Textarea::make('body_ar')->label(__('web.admin.body_ar'))->required()->rows(5)->maxLength(3000),
                Textarea::make('body_en')->label(__('web.admin.body_en'))->rows(5)->maxLength(3000),
            ]),
            Section::make(__('admin.announcements.section_publishing'))->columns(2)->columnSpanFull()->schema([
                Toggle::make('is_published')->label(__('web.admin.published'))->inline(false),
                Toggle::make('is_pinned')->label(__('web.admin.pinned'))->inline(false)->helperText(__('admin.announcements.pinned_hint')),
                DateTimePicker::make('published_at')->label(__('web.admin.published_at'))->seconds(false)->native(false)
                    ->helperText(__('admin.announcements.publish_at_hint')),
                DateTimePicker::make('expires_at')->label(__('web.admin.expires_at'))->seconds(false)->native(false)
                    ->rule(fn ($get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                        $start = $get('published_at');
                        if ($value && $start && strtotime($value) <= strtotime($start)) {
                            $fail(__('validation.after', ['attribute' => __('web.admin.expires_at'), 'date' => __('web.admin.published_at')]));
                        }
                    })
                    ->helperText(__('admin.announcements.expires_hint')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $states = __('admin.announcements.states');

        return $table
            ->columns([
                TextColumn::make('title_ar')->label(__('web.admin.title_ar'))->searchable(['title_ar', 'title_en'])->limit(60)->weight('bold')
                    ->icon(fn (Announcement $a) => $a->is_pinned ? 'heroicon-m-bookmark' : null)->iconColor('warning'),
                TextColumn::make('type')->label(__('web.admin.type'))->badge(),
                TextColumn::make('state')->label(__('admin.announcements.state'))->badge()
                    ->state(fn (Announcement $a) => $a->state())
                    ->formatStateUsing(fn (string $state) => $states[$state] ?? $state)
                    ->color(fn (string $state) => ['live' => 'success', 'scheduled' => 'info', 'expired' => 'gray', 'draft' => 'warning'][$state] ?? 'gray'),
                TextColumn::make('published_at')->label(__('web.admin.published_at'))->dateTime('Y-m-d H:i')->sortable()->placeholder('—'),
                TextColumn::make('expires_at')->label(__('web.admin.expires_at'))->dateTime('Y-m-d H:i')->placeholder('—')->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('type')->label(__('web.admin.type'))->options(AnnouncementType::class),
                SelectFilter::make('state')->label(__('admin.announcements.state'))->options($states)
                    ->query(fn (Builder $q, array $data) => match ($data['value'] ?? null) {
                        'draft' => $q->where('is_published', false),
                        'expired' => $q->where('is_published', true)->whereNotNull('expires_at')->where('expires_at', '<=', now()),
                        'scheduled' => $q->where('is_published', true)->where('published_at', '>', now()),
                        'live' => $q->visible(),
                        default => $q,
                    }),
            ])
            ->recordActions([
                Action::make('viewOnSite')->label(__('admin.announcements.view_on_site'))->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(route('site.announcements'), shouldOpenInNewTab: true)
                    ->visible(fn (Announcement $a) => $a->state() === 'live'),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAnnouncements::route('/')];
    }
}
