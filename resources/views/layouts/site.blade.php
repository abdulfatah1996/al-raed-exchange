@php
    $locale = app()->getLocale();
    $dir = config("locales.supported.$locale.dir", 'rtl');
    $other = $locale === 'ar' ? 'en' : 'ar';
    $wa = \App\Support\SiteCatalog::whatsappUrl();
    $nav = [
        'site.home' => __('web.nav.home'),
        'site.services' => __('web.nav.services'),
        'site.track' => __('web.nav.track'),
        'site.announcements' => __('web.nav.announcements'),
        'site.contact' => __('web.nav.contact'),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ __('site.brand') }}</title>
    <meta name="description" content="{{ __('web.meta_description') }}">
    <meta name="theme-color" content="#0b1f3a">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <meta property="og:title" content="{{ __('site.brand') }} — {{ __('site.tagline') }}">
    <meta property="og:description" content="{{ __('web.meta_description') }}">
    <link rel="alternate" hreflang="{{ $other }}" href="{{ route('lang.switch', $other) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex flex-col">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-50 focus:rounded-lg focus:bg-gold-500 focus:px-4 focus:py-2 focus:font-bold focus:text-navy-950">{{ __('web.skip') }}</a>

@php $banner = \App\Models\Announcement::siteBanner(); @endphp
@if ($banner)
    <div class="{{ $banner->type === \App\Enums\AnnouncementType::Warning ? 'bg-bad' : 'bg-gold-500' }} {{ $banner->type === \App\Enums\AnnouncementType::Warning ? 'text-white' : 'text-navy-950' }}" role="status">
        <a href="{{ route('site.announcements') }}" class="container-site flex items-center justify-center gap-2 py-2.5 text-center text-sm font-bold hover:underline">
            @include('site.partials.icon', ['name' => $banner->type === \App\Enums\AnnouncementType::Warning ? 'alert' : 'megaphone', 'class' => 'size-4 shrink-0'])
            <span>{{ $banner->title }}</span>
        </a>
    </div>
@endif

<header class="sticky top-0 z-40 border-b border-navy-900/8 bg-white/85 backdrop-blur-md">
    <div class="container-site flex h-16 items-center justify-between gap-4">
        <a href="{{ route('site.home') }}" aria-label="{{ __('site.brand') }}">@include('site.partials.logo', ['size' => 'size-9'])</a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('web.nav.menu') }}">
            @foreach ($nav as $route => $label)
                <a href="{{ route($route) }}" @class([
                    'rounded-lg px-3 py-2 text-[15px] font-semibold transition',
                    'text-navy-950 bg-navy-50' => request()->routeIs($route),
                    'text-navy-900/70 hover:text-navy-950 hover:bg-navy-50' => ! request()->routeIs($route),
                ]) @if(request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('lang.switch', $other) }}" class="hidden rounded-lg px-3 py-2 text-sm font-bold text-navy-900/70 hover:bg-navy-50 sm:inline-flex" hreflang="{{ $other }}" lang="{{ $other }}">{{ config("locales.supported.$other.name") }}</a>
            <a href="{{ route('site.request') }}" class="btn-gold hidden !py-2.5 sm:inline-flex">{{ __('web.nav.request') }}</a>

            <details class="group relative lg:hidden">
                <summary class="flex size-11 cursor-pointer list-none items-center justify-center rounded-xl border border-navy-900/10 text-navy-950 [&::-webkit-details-marker]:hidden" aria-label="{{ __('web.nav.menu') }}">
                    @include('site.partials.icon', ['name' => 'menu', 'class' => 'size-6 group-open:hidden'])
                    @include('site.partials.icon', ['name' => 'x', 'class' => 'size-6 hidden group-open:block'])
                </summary>
                <div class="absolute end-0 top-14 w-64 rounded-2xl border border-navy-900/10 bg-white p-2 shadow-2xl">
                    @foreach ($nav as $route => $label)
                        <a href="{{ route($route) }}" class="block rounded-lg px-4 py-3 font-semibold text-navy-900 hover:bg-navy-50">{{ $label }}</a>
                    @endforeach
                    <a href="{{ route('lang.switch', $other) }}" class="block rounded-lg px-4 py-3 font-semibold text-navy-900/70 hover:bg-navy-50" lang="{{ $other }}">{{ config("locales.supported.$other.name") }}</a>
                    <a href="{{ route('site.request') }}" class="btn-gold mt-2 w-full">{{ __('web.nav.request') }}</a>
                </div>
            </details>
        </div>
    </div>
</header>

<main id="main" class="flex-1">
    @yield('content')
</main>

<footer class="mt-20 bg-navy-950 text-white">
    <div class="container-site grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <div class="lg:col-span-2">
            @include('site.partials.logo', ['dark' => true])
            <p class="mt-4 max-w-sm text-[15px] leading-7 text-white/65">{{ __('web.footer.about') }}</p>
            <p class="mt-3 text-sm font-bold text-gold-300">{{ __('site.tagline') }}</p>
        </div>
        <div>
            <h2 class="text-sm font-bold text-white/50">{{ __('web.footer.links') }}</h2>
            <ul class="mt-4 space-y-2.5 text-[15px]">
                <li><a class="text-white/80 hover:text-gold-300" href="{{ route('site.request') }}">{{ __('web.nav.request') }}</a></li>
                @foreach ($nav as $route => $label)
                    <li><a class="text-white/80 hover:text-gold-300" href="{{ route($route) }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-bold text-white/50">{{ __('web.footer.policies') }}</h2>
            <ul class="mt-4 space-y-2.5 text-[15px]">
                @foreach (['instructions', 'privacy', 'terms', 'legal'] as $p)
                    <li><a class="text-white/80 hover:text-gold-300" href="{{ route('site.page', $p) }}">{{ __("web.pages.$p") }}</a></li>
                @endforeach
            </ul>
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white/8 px-4 py-2.5 font-bold text-white hover:bg-white/15">
                @include('site.partials.icon', ['name' => 'whatsapp', 'class' => 'size-5 text-gold-300'])
                <span class="num">{{ config('alraed.whatsapp_display') }}</span>
            </a>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-site flex flex-col gap-2 py-5 text-[13px] text-white/45 sm:flex-row sm:items-center sm:justify-between">
            <span>© {{ now()->year }} {{ __('site.brand') }} — {{ __('web.footer.rights') }}</span>
            <a href="{{ url('/alraed') }}" class="hover:text-white/80" rel="nofollow">{{ __('web.footer.staff') }}</a>
        </div>
    </div>
</footer>

<a href="{{ $wa }}" target="_blank" rel="noopener" aria-label="{{ __('web.hero.cta_whatsapp') }}"
   class="fixed bottom-5 end-5 z-40 flex size-14 items-center justify-center rounded-full bg-[#25d366] text-white shadow-[0_10px_30px_-6px_rgb(37_211_102/.6)] transition hover:scale-105">
    @include('site.partials.icon', ['name' => 'whatsapp', 'class' => 'size-7', 'stroke' => 2])
</a>
@stack('scripts')
</body>
</html>
