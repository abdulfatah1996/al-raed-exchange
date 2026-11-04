<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\SetLocale;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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
            ->profile(isSimple: false)
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()],
                isRequired: fn (): bool => (bool) config('alraed.require_mfa'),
            )
            ->brandName(fn (): string => __('site.brand'))
            ->colors([
                'primary' => self::palette(['#f1f4f9', '#e2e8f1', '#c5d1e3', '#9cb0cd', '#6c86ae', '#45618d', '#2d4a74', '#1c3a63', '#12294a', '#0b1f3a', '#06121f']),
                'gray' => Color::Slate,
                'success' => Color::hex('#2f7d57'),
                'danger' => Color::hex('#b42318'),
                'warning' => self::palette(['#fbf7ea', '#f6edcc', '#efdd9c', '#e6cc7a', '#d8b54b', '#c9a227', '#a8861c', '#866a17', '#6b5516', '#574614', '#322708']),
                'info' => Color::Sky,
            ])
            ->favicon(asset('favicon.svg'))
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
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
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn (): string => view('filament.login-hero')->render(),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => view('filament.login-footer')->render(),
            )
            ->navigationGroups([
                NavigationGroup::make(fn (): string => __('resources.groups.operations')),
                NavigationGroup::make(fn (): string => __('resources.groups.accounts')),
                NavigationGroup::make(fn (): string => __('resources.groups.settings'))->collapsed(),
            ])
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


    /** Brand palette (50 → 950) from exact hex values, so shades stay on-brand. */
    private static function palette(array $hex): array
    {
        return array_combine([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950], array_map(fn (string $h) => Color::convertToOklch($h), $hex));
    }
}
