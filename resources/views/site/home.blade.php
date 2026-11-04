@extends('layouts.site')

@php $wa = \App\Support\SiteCatalog::whatsappUrl(); @endphp

@section('content')
{{-- ── Hero ───────────────────────────────────────────── --}}
<section class="relative overflow-hidden bg-navy-950 text-white">
    <div class="pointer-events-none absolute inset-0 opacity-[.07]" style="background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:44px 44px"></div>
    <div class="pointer-events-none absolute -top-40 left-1/2 size-[640px] -translate-x-1/2 rounded-full bg-gold-500/15 blur-3xl"></div>

    <div class="container-site relative grid items-center gap-12 py-16 sm:py-20 lg:grid-cols-[1.15fr_.85fr] lg:py-24">
        <div>
            <span class="eyebrow rise">@include('site.partials.icon', ['name' => 'map', 'class' => 'size-4']) {{ __('web.hero.eyebrow') }}</span>
            <h1 class="rise rise-2 mt-5 text-[34px] font-extrabold leading-[1.25] tracking-tight sm:text-5xl sm:leading-[1.2]">
                {{ __('web.hero.title') }}
            </h1>
            <p class="rise rise-3 mt-5 max-w-xl text-[17px] leading-8 text-white/75">{{ __('web.hero.lead') }}</p>
            <div class="rise rise-4 mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('site.request') }}" class="btn-gold">
                    {{ __('web.hero.cta_request') }}
                    @include('site.partials.icon', ['name' => 'arrow', 'class' => 'size-5 flip-rtl'])
                </a>
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn-ghost">
                    @include('site.partials.icon', ['name' => 'whatsapp', 'class' => 'size-5 text-gold-300'])
                    {{ __('web.hero.cta_whatsapp') }}
                </a>
            </div>
            <p class="mt-8 text-lg font-extrabold tracking-wide text-gold-300">{{ __('site.tagline') }}</p>
        </div>

        {{-- Illustrative request card (an example, not a live rate) --}}
        <div class="rise rise-3 relative mx-auto w-full max-w-md" aria-hidden="true">
            <div class="absolute -inset-3 rounded-[28px] bg-gradient-to-br from-gold-500/30 via-transparent to-transparent blur-xl"></div>
            <div class="relative rounded-3xl border border-white/10 bg-white/[.06] p-6 backdrop-blur">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-white/60">{{ __('web.hero.card_title') }}</span>
                    <span class="num rounded-md bg-white/10 px-2 py-1 font-mono text-xs text-white/70">RQ-7K4M2Q9</span>
                </div>
                <div class="mt-5 rounded-2xl bg-white p-4 text-navy-950">
                    <div class="text-xs font-bold text-navy-900/50">{{ __('web.hero.card_from') }}</div>
                    <div class="mt-1 flex items-center gap-2 text-2xl font-extrabold"><span class="num">{{ __('web.hero.card_from_value') }}</span></div>
                </div>
                <div class="relative z-10 -my-3 flex justify-center">
                    <span class="flex size-10 items-center justify-center rounded-full border-4 border-navy-950 bg-gold-500 text-navy-950">
                        @include('site.partials.icon', ['name' => 'swap', 'class' => 'size-5 rotate-90', 'stroke' => 2.2])
                    </span>
                </div>
                <div class="rounded-2xl bg-white p-4 text-navy-950">
                    <div class="text-xs font-bold text-navy-900/50">{{ __('web.hero.card_to') }}</div>
                    <div class="mt-1 flex items-center gap-2 text-lg font-extrabold">
                        @include('site.partials.icon', ['name' => 'bank', 'class' => 'size-5 text-gold-600'])
                        {{ __('web.hero.card_to_value') }}
                    </div>
                </div>
                <div class="mt-5 flex items-center gap-2 text-sm font-bold text-gold-300">
                    <span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-gold-400 opacity-60"></span><span class="relative inline-flex size-2.5 rounded-full bg-gold-400"></span></span>
                    {{ __('web.hero.card_status') }}
                </div>
                <p class="mt-2 text-[13px] leading-6 text-white/55">{{ __('web.hero.card_note') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- ── Trust ──────────────────────────────────────────── --}}
<section class="container-site relative z-10 -mt-8">
    <div class="grid gap-4 md:grid-cols-3">
        @foreach (__('web.trust') as $i => $item)
            <div class="card p-6">
                <span class="flex size-11 items-center justify-center rounded-xl bg-gold-50 text-gold-600">
                    @include('site.partials.icon', ['name' => ['shield', 'tag', 'search'][$i] ?? 'check', 'class' => 'size-6'])
                </span>
                <h2 class="mt-4 text-lg font-extrabold text-navy-950">{{ $item['title'] }}</h2>
                <p class="mt-1.5 text-[15px] leading-7 text-navy-900/70">{{ $item['text'] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ── How it works ───────────────────────────────────── --}}
<section class="container-site mt-20">
    <h2 class="section-title">{{ __('web.steps_title') }}</h2>
    <p class="section-lead">{{ __('web.steps_lead') }}</p>
    <ol class="relative mt-10 grid gap-6 md:grid-cols-5">
        <div class="absolute inset-x-[10%] top-6 hidden h-px bg-gradient-to-r from-gold-500/0 via-gold-500/60 to-gold-500/0 md:block" aria-hidden="true"></div>
        @foreach (__('web.steps') as $i => $step)
            <li class="relative flex gap-4 md:flex-col md:items-center md:text-center">
                <span class="num relative z-10 flex size-12 shrink-0 items-center justify-center rounded-full border-2 border-gold-500 bg-white text-lg font-extrabold text-navy-950 shadow-sm">{{ $i + 1 }}</span>
                <div>
                    <h3 class="font-extrabold text-navy-950 md:mt-3">{{ $step['title'] }}</h3>
                    <p class="mt-1 text-[14px] leading-6 text-navy-900/65">{{ $step['text'] }}</p>
                </div>
            </li>
        @endforeach
    </ol>
</section>

{{-- ── Services by region ─────────────────────────────── --}}
<section class="container-site mt-24">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="section-title">{{ __('web.services_title') }}</h2>
            <p class="section-lead">{{ __('web.services_lead') }}</p>
        </div>
        <a href="{{ route('site.services') }}" class="btn-outline shrink-0">{{ __('web.services_all') }} @include('site.partials.icon', ['name' => 'arrow', 'class' => 'size-4 flip-rtl'])</a>
    </div>
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($groups as $group => $services)
            <div class="card group p-5 transition hover:-translate-y-0.5 hover:border-gold-500/40">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-navy-900 text-gold-300">
                        @include('site.partials.icon', ['name' => match ($group) { 'crypto' => 'coin', 'paypal' => 'wallet', 'international', 'other' => 'globe', default => 'bank' }, 'class' => 'size-5'])
                    </span>
                    <h3 class="text-lg font-extrabold text-navy-950">{{ __("web.groups.$group") }}</h3>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($services as $service)
                        <a href="{{ route('site.request', ['service' => $service->id]) }}" class="chip hover:border-gold-500 hover:bg-gold-50">{{ $service->name }}</a>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-navy-900/60">—</p>
        @endforelse
    </div>
</section>

{{-- ── Delivery in Gaza ───────────────────────────────── --}}
@if ($deliveryMethods->isNotEmpty())
<section class="container-site mt-16">
    <div class="card flex flex-col gap-5 p-6 sm:flex-row sm:items-center">
        <h2 class="shrink-0 text-lg font-extrabold text-navy-950">{{ __('web.delivery_title') }}</h2>
        <div class="flex flex-wrap gap-2">
            @foreach ($deliveryMethods as $method)
                <span class="chip bg-white">@include('site.partials.icon', ['name' => 'check', 'class' => 'size-4 text-ok']) {{ $method->name }}</span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ── Announcements ──────────────────────────────────── --}}
@if ($announcements->isNotEmpty())
<section class="container-site mt-24">
    <div class="flex items-end justify-between gap-4">
        <h2 class="section-title">{{ __('web.announcements_title') }}</h2>
        <a href="{{ route('site.announcements') }}" class="font-bold text-gold-600 hover:text-navy-950">{{ __('web.announcements_all') }}</a>
    </div>
    <div class="mt-6 grid gap-4 md:grid-cols-3">
        @foreach ($announcements as $a)
            @include('site.partials.announcement', ['a' => $a])
        @endforeach
    </div>
</section>
@endif

{{-- ── CTA band ───────────────────────────────────────── --}}
<section class="container-site mt-24">
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-gold-400 to-gold-600 px-6 py-10 text-navy-950 sm:px-12">
        <div class="pointer-events-none absolute -end-16 -top-16 size-64 rounded-full border-[28px] border-white/15"></div>
        <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-extrabold sm:text-3xl">{{ __('web.cta_band_title') }}</h2>
                <p class="mt-2 text-[16px] font-semibold text-navy-950/75">{{ __('web.cta_band_text') }}</p>
            </div>
            <a href="{{ route('site.request') }}" class="btn-navy shrink-0 !px-7 !py-3.5">{{ __('web.hero.cta_request') }} @include('site.partials.icon', ['name' => 'arrow', 'class' => 'size-5 flip-rtl'])</a>
        </div>
    </div>
</section>
@endsection
