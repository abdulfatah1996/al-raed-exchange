@extends('layouts.site', ['title' => __('web.nav.services')])

@section('content')
@include('site.partials.page-head', ['heading' => __('web.services_title'), 'lead' => __('web.services_lead')])
<div class="container-site mt-10 space-y-6">
    @foreach ($groups as $group => $services)
        <section class="card p-6">
            <h2 class="text-xl font-extrabold text-navy-950">{{ __("web.groups.$group") }}</h2>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <li>
                        <a href="{{ route('site.request', ['service' => $service->id]) }}" class="flex h-full items-start justify-between gap-3 rounded-xl border border-navy-900/10 p-4 transition hover:border-gold-500 hover:bg-gold-50/50">
                            <span>
                                <span class="block font-bold text-navy-950">{{ $service->name }}</span>
                                <span class="mt-1 block text-[13px] text-navy-900/55">
                                    {{ __('web.service_direction.' . $service->direction->value) }}@if($service->country) · {{ $service->country->name }}@endif
                                </span>
                                @php $desc = app()->getLocale() === 'en' ? ($service->description_en ?: $service->description_ar) : $service->description_ar; @endphp
                                @if ($desc)<span class="mt-2 block text-[14px] leading-6 text-navy-900/70">{{ $desc }}</span>@endif
                            </span>
                            @include('site.partials.icon', ['name' => 'arrow', 'class' => 'mt-1 size-4 shrink-0 text-gold-600 flip-rtl'])
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
@endsection
