<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\SetLocale;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AlraedPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $css = public_path('css/alraed-panel.css');

        return $panel
            ->default()
            ->id('alraed')
            ->path('alraed')
            ->login(Login::class)
            ->brandName(fn (): string => __('site.brand'))
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
                'success' => Color::Emerald,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'info' => Color::Sky,
            ])
            ->font('Cairo')
            ->darkMode(true)
            ->sidebarWidth('17rem')
            ->spa()
            ->spaUrlExceptions(fn (): array => [url('/lang/*')])
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->assets([
                Css::make('alraed-panel', asset('css/alraed-panel.css') . '?v=' . (file_exists($css) ? filemtime($css) : '1')),
            ])
            ->userMenuItems([
                Action::make('language')
                    ->label(fn (): string => __('site.switch_language'))
                    ->icon('heroicon-o-language')
                    ->url(fn (): string => route('lang.switch', app()->getLocale() === 'ar' ? 'en' : 'ar')),
            ])
            ->navigationItems([
                $this->placeholder('users', 'heroicon-o-user', 4),
                $this->placeholder('reports', 'heroicon-o-chart-bar', 5),
            ])
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): string => view('filament.brand')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => view('filament.topbar-title', ['title' => __('dashboard.title')])->render(),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => view('filament.topbar-user')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_START,
                fn (): string => request()->routeIs('filament.alraed.auth.login') ? view('filament.login-hero')->render() : '',
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => view('filament.login-footer')->render(),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /** Temporary menu entry until the real module is built. */
    private function placeholder(string $key, string $icon, int $sort): NavigationItem
    {
        return NavigationItem::make($key)
            ->label(fn (): string => __("dashboard.nav.{$key}"))
            ->icon($icon)
            ->url('#')
            ->badge(fn (): string => __('dashboard.soon'))
            ->isActiveWhen(fn (): bool => false)
            ->sort($sort);
    }
}
