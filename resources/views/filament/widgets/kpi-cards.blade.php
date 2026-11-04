<x-filament-widgets::widget>
    <div class="ar-kpis">
        @foreach ($cards as $c)
            <a href="{{ $c['url'] }}" class="ar-kpi ar-kpi--{{ $c['tone'] }}">
                <span class="ar-kpi-icon"><x-filament::icon :icon="$c['icon']" class="ar-kpi-icon-svg" /></span>
                <span class="ar-kpi-body">
                    <span class="ar-kpi-value">{{ $c['value'] }}</span>
                    <span class="ar-kpi-label">{{ $c['label'] }}</span>
                </span>
                <x-filament::icon icon="heroicon-m-chevron-right" class="ar-kpi-go" />
            </a>
        @endforeach
    </div>
</x-filament-widgets::widget>
