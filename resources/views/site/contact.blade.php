@extends('layouts.site', ['title' => __('web.nav.contact')])

@section('content')
@include('site.partials.page-head', ['heading' => __('web.contact.title'), 'lead' => __('web.contact.lead')])
<div class="container-site mt-10 grid gap-4 md:grid-cols-3">
    <a href="{{ \App\Support\SiteCatalog::whatsappUrl() }}" target="_blank" rel="noopener" class="card p-6 transition hover:border-gold-500">
        <span class="flex size-11 items-center justify-center rounded-xl bg-[#25d366]/10 text-[#1a9e4b]">@include('site.partials.icon', ['name' => 'whatsapp', 'class' => 'size-6'])</span>
        <h2 class="mt-4 text-sm font-bold text-navy-900/55">{{ __('web.contact.whatsapp') }}</h2>
        <p class="num mt-1 text-xl font-extrabold text-navy-950">{{ config('alraed.whatsapp_display') }}</p>
    </a>
    <div class="card p-6">
        <span class="flex size-11 items-center justify-center rounded-xl bg-gold-50 text-gold-600">@include('site.partials.icon', ['name' => 'map', 'class' => 'size-6'])</span>
        <h2 class="mt-4 text-sm font-bold text-navy-900/55">{{ __('web.contact.location') }}</h2>
        <p class="mt-1 text-xl font-extrabold text-navy-950">{{ __('web.contact.location_value') }}</p>
    </div>
    <div class="card p-6">
        <span class="flex size-11 items-center justify-center rounded-xl bg-gold-50 text-gold-600">@include('site.partials.icon', ['name' => 'clock', 'class' => 'size-6'])</span>
        <h2 class="mt-4 text-sm font-bold text-navy-900/55">{{ __('web.contact.hours') }}</h2>
        <p class="mt-1 text-lg font-extrabold text-navy-950">{{ __('web.contact.hours_value') }}</p>
    </div>
    <div class="flex gap-4 rounded-2xl border border-bad/20 bg-bad-50 p-6 md:col-span-3">
        @include('site.partials.icon', ['name' => 'alert', 'class' => 'size-7 shrink-0 text-bad'])
        <div>
            <h2 class="font-extrabold text-bad">{{ __('web.contact.warning_title') }}</h2>
            <p class="mt-1 leading-7 text-navy-900/80">{{ __('web.contact.warning_text') }}</p>
        </div>
    </div>
</div>
@endsection
