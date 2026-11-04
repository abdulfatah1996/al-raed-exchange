@extends('layouts.site', ['title' => __('web.track.title')])

@section('content')
@include('site.partials.page-head', ['heading' => __('web.track.title'), 'lead' => __('web.track.lead')])

<div class="container-site mt-10 grid max-w-4xl gap-6">
    <form method="POST" action="{{ route('site.track.lookup') }}" class="card grid gap-5 p-6 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        @csrf
        <div>
            <label for="reference" class="field-label">{{ __('web.track.reference') }}</label>
            <input id="reference" name="reference" value="{{ old('reference', request('reference')) }}" required dir="ltr" placeholder="RQ-XXXXXXX" autocapitalize="characters" class="field text-start font-mono uppercase">
        </div>
        <div>
            <label for="phone" class="field-label">{{ __('web.track.phone') }}</label>
            <input id="phone" name="phone" value="{{ old('phone', request('phone')) }}" required type="tel" inputmode="tel" dir="ltr" class="field text-start">
        </div>
        <button class="btn-navy !py-3">@include('site.partials.icon', ['name' => 'search', 'class' => 'size-5']) {{ __('web.track.submit') }}</button>
        @if ($errors->any())<p class="field-error sm:col-span-3">{{ $errors->first() }}</p>@endif
    </form>

    @if ($notFound)
        <div class="rounded-2xl border border-bad/20 bg-bad-50 p-5 font-semibold text-bad" role="status">{{ __('web.track.not_found') }}</div>
    @endif

    @if ($result)
        @php $stage = $result->publicStage(); $keys = ['received', 'pricing', 'verifying', 'executing', 'delivered']; @endphp
        <section class="card p-6" aria-live="polite">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-extrabold text-navy-950">{{ __('web.track.result_title') }}</h2>
                <span class="num rounded-lg bg-navy-50 px-3 py-1 font-mono text-sm font-bold text-navy-900">{{ $result->reference }}</span>
            </div>
            <p class="mt-1 text-sm text-navy-900/55">{{ __('web.track.submitted') }} <span class="num">{{ $result->created_at->format('Y-m-d H:i') }}</span></p>

            @if ($stage['step'] === 0)
                <div class="mt-6 rounded-xl bg-navy-50 p-5 font-semibold text-navy-900/80">{{ __('web.track.closed_text') }}</div>
            @else
                <ol class="mt-8 grid gap-4 sm:grid-cols-5">
                    @foreach ($keys as $i => $key)
                        @php $n = $i + 1; $done = $n < $stage['step']; $current = $n === $stage['step']; @endphp
                        <li class="flex items-center gap-3 sm:flex-col sm:text-center" @if($current) aria-current="step" @endif>
                            <span @class([
                                'flex size-10 shrink-0 items-center justify-center rounded-full border-2 font-extrabold num',
                                'border-ok bg-ok text-white' => $done || ($current && $key === 'delivered'),
                                'border-gold-500 bg-gold-50 text-navy-950 ring-4 ring-gold-500/20' => $current && $key !== 'delivered',
                                'border-navy-900/15 bg-white text-navy-900/40' => ! $done && ! $current,
                            ])>
                                @if ($done || ($current && $key === 'delivered')) @include('site.partials.icon', ['name' => 'check', 'class' => 'size-5', 'stroke' => 2.6]) @else {{ $n }} @endif
                            </span>
                            <span @class(['text-sm font-bold', 'text-navy-950' => $done || $current, 'text-navy-900/45' => ! $done && ! $current])>{{ __("web.track.stages.$key") }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif

            <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-navy-900/8 pt-5">
                <span class="text-sm font-semibold text-navy-900/60">{{ __('web.track.help') }}</span>
                <a href="{{ \App\Support\SiteCatalog::whatsappUrl(__('web.request.done_whatsapp_text', ['ref' => $result->reference])) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 font-bold text-gold-600 hover:text-navy-950">
                    @include('site.partials.icon', ['name' => 'whatsapp', 'class' => 'size-5']) {{ __('web.contact.whatsapp') }}
                </a>
            </div>
        </section>
    @endif
</div>
@endsection
