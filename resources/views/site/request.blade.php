@extends('layouts.site', ['title' => __('web.request.title')])

@section('content')
@include('site.partials.page-head', ['heading' => __('web.request.title'), 'lead' => __('web.request.lead')])

<div class="container-site mt-10 grid gap-8 lg:grid-cols-[1fr_320px]">
    <form method="POST" action="{{ route('site.request.store') }}" class="space-y-6" novalidate>
        @csrf
        {{-- honeypot: hidden from people and screen readers --}}
        <div class="absolute -start-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-bad/20 bg-bad-50 p-4 text-sm font-semibold text-bad" role="alert">
                <ul class="list-disc space-y-1 ps-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <fieldset class="card p-6">
            <legend class="sr-only">{{ __('web.request.section_you') }}</legend>
            <h2 class="text-lg font-extrabold text-navy-950">{{ __('web.request.section_you') }}</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="field-label">{{ __('web.request.name') }}</label>
                    <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="field" @error('name') aria-invalid="true" @enderror>
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="field-label">{{ __('web.request.phone') }}</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" required type="tel" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="+970 59 000 0000" class="field text-start" aria-describedby="phone-hint" @error('phone') aria-invalid="true" @enderror>
                    <p id="phone-hint" class="field-hint">{{ __('web.request.phone_hint') }}</p>
                    @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </fieldset>

        <fieldset class="card p-6">
            <legend class="sr-only">{{ __('web.request.section_transfer') }}</legend>
            <h2 class="text-lg font-extrabold text-navy-950">{{ __('web.request.section_transfer') }}</h2>

            <div class="mt-5">
                <span class="field-label">{{ __('web.request.operation') }}</span>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4" role="radiogroup">
                    @foreach ($operations as $op)
                        <label class="cursor-pointer">
                            <input type="radio" name="operation" value="{{ $op->value }}" class="peer sr-only" @checked(old('operation', 'receive') === $op->value)>
                            <span class="block rounded-xl border border-navy-900/15 bg-white px-3 py-3 text-center text-sm font-bold text-navy-900/75 transition peer-checked:border-gold-500 peer-checked:bg-gold-50 peer-checked:text-navy-950 peer-focus-visible:ring-4 peer-focus-visible:ring-gold-500/30">{{ $op->getLabel() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('operation')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="service_id" class="field-label">{{ __('web.request.service') }}</label>
                    <select id="service_id" name="service_id" class="field">
                        <option value="">{{ __('web.request.not_sure') }}</option>
                        @foreach ($groups as $group => $services)
                            <optgroup label="{{ __("web.groups.$group") }}">
                                @foreach ($services as $s)
                                    <option value="{{ $s->id }}" @selected((int) old('service_id', $preselectService) === $s->id)>{{ $s->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('service_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="amount" class="field-label">{{ __('web.request.amount') }}</label>
                    <input id="amount" name="amount" value="{{ old('amount') }}" required inputmode="decimal" dir="ltr" placeholder="1000" class="field text-start num" @error('amount') aria-invalid="true" @enderror>
                    @error('amount')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="currency_id" class="field-label">{{ __('web.request.currency') }}</label>
                    <select id="currency_id" name="currency_id" required class="field">
                        <option value="">{{ __('web.request.choose') }}</option>
                        @foreach ($currencies as $c)
                            <option value="{{ $c->id }}" @selected((int) old('currency_id') === $c->id)>{{ $c->code }} — {{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('currency_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="country_id" class="field-label">{{ __('web.request.country') }}</label>
                    <select id="country_id" name="country_id" class="field">
                        <option value="">{{ __('web.request.not_sure') }}</option>
                        @foreach ($countries as $c)
                            <option value="{{ $c->id }}" @selected((int) old('country_id') === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="delivery_method_id" class="field-label">{{ __('web.request.delivery') }}</label>
                    <select id="delivery_method_id" name="delivery_method_id" class="field">
                        <option value="">{{ __('web.request.not_sure') }}</option>
                        @foreach ($deliveryMethods as $m)
                            <option value="{{ $m->id }}" @selected((int) old('delivery_method_id') === $m->id)>{{ $m->name }}</option>
                        @endforeach
                    </select>
                    @error('delivery_method_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="receive_via" class="field-label">{{ __('web.request.receive_via') }}</label>
                    <input id="receive_via" name="receive_via" value="{{ old('receive_via') }}" maxlength="150" class="field">
                </div>
                <div class="sm:col-span-2">
                    <label for="notes" class="field-label">{{ __('web.request.notes') }}</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="1000" class="field" placeholder="{{ __('web.request.notes_hint') }}">{{ old('notes') }}</textarea>
                </div>
            </div>
        </fieldset>

        <div class="card p-6">
            <label class="flex items-start gap-3 text-[15px] leading-7 text-navy-900/80">
                <input type="checkbox" name="consent" value="1" required @checked(old('consent')) class="mt-1.5 size-5 shrink-0 rounded border-navy-900/30 accent-gold-500">
                <span>{{ __('web.request.consent') }} <a href="{{ route('site.page', 'instructions') }}" target="_blank" class="font-bold text-gold-600 underline underline-offset-4">{{ __('web.request.consent_link') }}</a>.</span>
            </label>
            @error('consent')<p class="field-error">{{ $message }}</p>@enderror
            <button type="submit" class="btn-gold mt-5 w-full !py-3.5 text-base sm:w-auto sm:!px-10">{{ __('web.request.submit') }}</button>
        </div>
    </form>

    <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
        <div class="rounded-2xl border border-gold-500/30 bg-gold-50 p-5">
            <div class="flex items-center gap-2 font-extrabold text-navy-950">@include('site.partials.icon', ['name' => 'lock', 'class' => 'size-5 text-gold-600']) {{ __('web.contact.warning_title') }}</div>
            <p class="mt-2 text-[14px] leading-7 text-navy-900/80">{{ __('web.request.safety') }}</p>
        </div>
        <div class="card p-5">
            <h2 class="font-extrabold text-navy-950">{{ __('web.steps_title') }}</h2>
            <ol class="mt-3 space-y-3">
                @foreach (__('web.steps') as $i => $step)
                    <li class="flex gap-3 text-[14px]"><span class="num flex size-6 shrink-0 items-center justify-center rounded-full bg-navy-900 text-xs font-bold text-gold-300">{{ $i + 1 }}</span><span class="font-semibold text-navy-900/80">{{ $step['title'] }}</span></li>
                @endforeach
            </ol>
        </div>
    </aside>
</div>
@endsection
