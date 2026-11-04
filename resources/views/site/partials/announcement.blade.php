@php
    $tone = match ($a->type) {
        \App\Enums\AnnouncementType::Warning => 'bg-bad-50 text-bad border-bad/20',
        \App\Enums\AnnouncementType::AccountChange => 'bg-gold-50 text-gold-600 border-gold-500/30',
        \App\Enums\AnnouncementType::Offer => 'bg-ok-50 text-ok border-ok/20',
        default => 'bg-navy-50 text-navy-800 border-navy-900/10',
    };
@endphp
<article class="card flex flex-col p-5 {{ $a->is_pinned ? 'ring-2 ring-gold-500/50' : '' }}">
    <div class="flex items-center justify-between gap-3">
        <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-bold {{ $tone }}">
            @include('site.partials.icon', ['name' => $a->type === \App\Enums\AnnouncementType::Warning ? 'alert' : 'megaphone', 'class' => 'size-3.5'])
            {{ $a->type->getLabel() }}
        </span>
        <time class="num text-xs font-semibold text-navy-900/50" datetime="{{ $a->published_at?->toDateString() }}">{{ $a->published_at?->format('Y-m-d') }}</time>
    </div>
    <h3 class="mt-3 text-lg font-extrabold leading-7 text-navy-950">{{ $a->title }}</h3>
    <p class="mt-2 whitespace-pre-line text-[15px] leading-7 text-navy-900/75">{{ $full ?? false ? $a->body : \Illuminate\Support\Str::limit($a->body, 180) }}</p>
</article>
