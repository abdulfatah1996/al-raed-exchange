<x-filament-widgets::widget>
    <div class="ar-kpis">
        @foreach ($cards as $c)
            <div class="ar-kpi">
                <div class="ar-kpi-top">
                    <span class="ar-kpi-icon ar-kpi-icon--{{ $c['tone'] }}">
                        <x-filament::icon :icon="$c['icon']" style="width:1.45rem;height:1.45rem;" />
                    </span>
                    <span class="ar-kpi-label">{{ $c['label'] }}</span>
                </div>
                <div class="ar-kpi-value">{{ $c['value'] }}@if ($c['unit'])<small>{{ $c['unit'] }}</small>@endif</div>
            </div>
        @endforeach
    </div>
    @if ($demo)<p class="ar-demo">{{ __('dashboard.demo_notice') }}</p>@endif
</x-filament-widgets::widget>
