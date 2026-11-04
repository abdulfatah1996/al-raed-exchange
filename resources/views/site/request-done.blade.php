@extends('layouts.site', ['title' => __('web.request.done_title')])

@section('content')
<div class="container-site max-w-2xl py-16 text-center">
    <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-ok-50 text-ok">@include('site.partials.icon', ['name' => 'check', 'class' => 'size-9', 'stroke' => 2.4])</span>
    <h1 class="mt-6 text-3xl font-extrabold text-navy-950">{{ __('web.request.done_title') }}</h1>
    <p class="mx-auto mt-3 max-w-md leading-8 text-navy-900/70">{{ __('web.request.done_lead') }}</p>

    <div class="card mx-auto mt-8 max-w-sm p-6">
        <div class="text-sm font-bold text-navy-900/55">{{ __('web.request.reference') }}</div>
        <div class="mt-2 flex items-center justify-center gap-3">
            <span id="ref" class="num font-mono text-3xl font-extrabold tracking-wider text-navy-950">{{ $reference }}</span>
            <button type="button" id="copy" data-done="{{ __('web.request.copied') }}" class="rounded-lg border border-navy-900/10 p-2 text-navy-900/60 hover:border-gold-500 hover:text-navy-950" aria-label="{{ __('web.request.copy') }}">
                @include('site.partials.icon', ['name' => 'copy', 'class' => 'size-5'])
            </button>
        </div>
        <p id="copy-status" class="mt-2 h-5 text-sm font-semibold text-ok" aria-live="polite"></p>
    </div>

    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
        <a href="{{ \App\Support\SiteCatalog::whatsappUrl(__('web.request.done_whatsapp_text', ['ref' => $reference])) }}" target="_blank" rel="noopener" class="btn-gold">
            @include('site.partials.icon', ['name' => 'whatsapp', 'class' => 'size-5']) {{ __('web.request.done_whatsapp') }}
        </a>
        <a href="{{ route('site.track') }}" class="btn-outline">{{ __('web.request.done_track') }}</a>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('copy')?.addEventListener('click', async (e) => {
    try { await navigator.clipboard.writeText(document.getElementById('ref').textContent.trim());
          document.getElementById('copy-status').textContent = e.currentTarget.dataset.done; } catch (_) {}
});
</script>
@endpush
@endsection
