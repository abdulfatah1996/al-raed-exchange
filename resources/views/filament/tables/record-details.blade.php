@php
    /** @var array<string, mixed> $items */
    $items = $getState() ?? [];
    $colors = [
        'success' => 'alraed-chip-success', 'danger' => 'alraed-chip-danger', 'warning' => 'alraed-chip-warning',
        'info' => 'alraed-chip-info', 'primary' => 'alraed-chip-primary',
    ];
@endphp

<dl class="alraed-details">
    @foreach ($items as $label => $item)
        @php
            $opts = is_array($item) && array_key_exists('value', $item) ? $item : ['value' => $item];
            $value = $opts['value'];
            $chip = null;
            if ($value instanceof \Filament\Support\Contracts\HasLabel) {
                $color = $value instanceof \Filament\Support\Contracts\HasColor ? $value->getColor() : null;
                $chip = $colors[is_string($color) ? $color : ''] ?? 'alraed-chip-gray';
                $value = $value->getLabel();
            } elseif ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof \DateTimeInterface) {
                $value = \Illuminate\Support\Carbon::instance($value)->format('Y-m-d H:i');
                $opts['ltr'] = true;
            } elseif (is_bool($value)) {
                $value = $value ? __('admin.common.yes') : __('admin.common.no');
            }
            $empty = $value === null || $value === '';
        @endphp
        <div @class(['alraed-details-item', 'alraed-details-wide' => $opts['wide'] ?? false])>
            <dt>{{ $label }}</dt>
            <dd>
                @if (array_key_exists('before', $opts) && $opts['before'] !== null)
                    <span class="alraed-diff">
                        <span class="alraed-diff-old"><small>{{ __('admin.audit.before') }}</small> <bdi>{{ $opts['before'] }}</bdi></span>
                        <span class="alraed-diff-new"><small>{{ __('admin.audit.after') }}</small> <bdi>{{ $value }}</bdi></span>
                    </span>
                @elseif ($empty)
                    <span class="alraed-details-empty">—</span>
                @elseif (filled($opts['url'] ?? null))
                    <a href="{{ $opts['url'] }}" target="_blank" rel="noopener noreferrer" class="alraed-details-link">{{ $value }}</a>
                @elseif ($chip)
                    <span class="alraed-chip {{ $chip }}">{{ $value }}</span>
                @elseif ($opts['code'] ?? false)
                    <bdi dir="ltr" translate="no" class="alraed-details-code">{{ $value }}</bdi>
                @elseif ($opts['ltr'] ?? false)
                    <bdi dir="ltr">{{ $value }}</bdi>
                @else
                    <span class="alraed-details-text">{{ $value }}</span>
                @endif
            </dd>
        </div>
    @endforeach
</dl>
