@php
    $paths = [
        'shield' => '<path d="M12 3l7 3v5c0 4.6-3 8.5-7 10-4-1.5-7-5.4-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
        'tag' => '<path d="M3 12V4a1 1 0 011-1h8l9 9-9 9-9-9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'arrow' => '<path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'bank' => '<path d="M3 10l9-6 9 6"/><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8"/><path d="M3 20h18"/>',
        'wallet' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><circle cx="16.5" cy="14.5" r="1"/>',
        'coin' => '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5c-.4-.9-1.4-1.5-2.5-1.5-1.5 0-2.5.8-2.5 2s1 1.7 2.5 2 2.5.8 2.5 2-1 2-2.5 2c-1.1 0-2.1-.6-2.5-1.5M12 6.5v1.5M12 16v1.5"/>',
        'check' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'alert' => '<path d="M12 3l9.5 17h-19L12 3z"/><path d="M12 10v4.5M12 17.5v.01"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'map' => '<path d="M12 21s-7-6.1-7-11.5A7 7 0 0112 2.5a7 7 0 017 7C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'megaphone' => '<path d="M3 10v4a1 1 0 001 1h2l5 4V5L6 9H4a1 1 0 00-1 1z"/><path d="M15 8.5a4.5 4.5 0 010 7M18 6a8 8 0 010 12"/>',
        'copy' => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 00-1-1H5a1 1 0 00-1 1v10a1 1 0 001 1h3"/>',
        'swap' => '<path d="M7 4L3 8l4 4"/><path d="M3 8h14"/><path d="M17 20l4-4-4-4"/><path d="M21 16H7"/>',
        'whatsapp' => '<path d="M4 20l1.3-4A8.5 8.5 0 1112 20.5a8.4 8.4 0 01-4-1L4 20z"/><path d="M9 9.2c.2 2.6 3.2 5.6 5.8 5.8l1.2-1.4-1.9-1-1 .8c-.9-.4-1.9-1.4-2.3-2.3l.8-1-1-1.9L9 9.2z"/>',
        'lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>',
    ];
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $stroke ?? 1.8 }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="{{ $class ?? 'size-5' }}">{!! $paths[$name] ?? '' !!}</svg>
