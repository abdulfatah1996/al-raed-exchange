{{-- Provisional brand mark (gold R + crown + transfer arcs) until the official logo file is supplied. --}}
<span class="inline-flex items-center gap-2.5">
    <svg viewBox="0 0 48 48" class="{{ $size ?? 'size-10' }} shrink-0" aria-hidden="true">
        <rect width="48" height="48" rx="12" fill="#0b1f3a"/>
        <path d="M11 27a13 13 0 0021.5 9.3" fill="none" stroke="#c9a227" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M37 21a13 13 0 00-21.5-9.3" fill="none" stroke="#c9a227" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M30.5 33.5l2.2 3 3.1-1.9M17.5 14.5l-2.2-3-3.1 1.9" fill="none" stroke="#c9a227" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M19.5 16.5l1.4 2 2.1-2.6 2.1 2.6 1.4-2v3h-7z" fill="#e6cc7a"/>
        <text x="24" y="32.5" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif" font-weight="700" font-size="15" fill="#e6cc7a">R</text>
    </svg>
    @unless($markOnly ?? false)
        <span class="leading-tight">
            <span class="block text-[17px] font-extrabold {{ $dark ?? false ? 'text-white' : 'text-navy-950' }}">{{ __('site.brand') }}</span>
            <span class="block text-[11px] font-semibold tracking-wide {{ $dark ?? false ? 'text-gold-300' : 'text-gold-600' }}">AL RAED EXCHANGE</span>
        </span>
    @endunless
</span>
