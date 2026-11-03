@php
    $current = app()->getLocale();
    $other = $current === 'ar' ? 'en' : 'ar';
    $label = config("locales.supported.{$other}.name");
@endphp
<a href="{{ route('lang.switch', $other) }}"
   title="{{ $label }}"
   style="display:inline-flex;align-items:center;gap:.4rem;padding:.35rem .75rem;border-radius:.6rem;font-size:.8rem;font-weight:600;border:1px solid rgba(201,162,39,.55);color:#C9A227;text-decoration:none;white-space:nowrap;">
    <x-filament::icon icon="heroicon-m-language" style="width:1rem;height:1rem;" />
    <span>{{ $label }}</span>
</a>
