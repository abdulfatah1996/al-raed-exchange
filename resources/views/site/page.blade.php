@extends('layouts.site', ['title' => __("web.pages.$slug")])

@section('content')
@include('site.partials.page-head', ['heading' => __("web.pages.$slug")])
<div class="container-site mt-10 grid gap-8 lg:grid-cols-[220px_1fr]">
    <nav class="flex gap-2 overflow-x-auto lg:flex-col" aria-label="{{ __('web.footer.policies') }}">
        @foreach ($pages as $p)
            <a href="{{ route('site.page', $p) }}" @class(['shrink-0 rounded-lg px-4 py-2.5 text-sm font-bold', 'bg-navy-900 text-white' => $p === $slug, 'text-navy-900/70 hover:bg-navy-50' => $p !== $slug])>{{ __("web.pages.$p") }}</a>
        @endforeach
    </nav>
    <article class="card p-6 sm:p-10">
        <p class="mb-6 rounded-xl border border-gold-500/30 bg-gold-50 px-4 py-3 text-sm font-semibold text-gold-600">{{ __('web.pages.draft') }}</p>
        <div class="prose-site">@include('site.pages.' . $slug . '-' . (app()->getLocale() === 'en' ? 'en' : 'ar'))</div>
    </article>
</div>
@endsection
