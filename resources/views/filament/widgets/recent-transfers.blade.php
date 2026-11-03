<x-filament-widgets::widget>
    <div class="ar-card">
        <div class="ar-card-head">
            <x-filament::icon icon="heroicon-o-document-text" class="ar-card-head-icon" style="width:1.35rem;height:1.35rem;" />
            <h3>{{ __('dashboard.recent') }}</h3>
        </div>
        <div class="ar-table-wrap">
            <table class="ar-table">
                <thead>
                    <tr>
                        <th>{{ __('dashboard.cols.ref') }}</th>
                        <th>{{ __('dashboard.cols.customer') }}</th>
                        <th>{{ __('dashboard.cols.amount') }}</th>
                        <th>{{ __('dashboard.cols.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr>
                            <td>{{ $r['ref'] }}</td>
                            <td>{{ $r['customer'] }}</td>
                            <td dir="ltr" style="text-align:start">{{ $r['amount'] }}</td>
                            <td><span class="ar-badge ar-badge--{{ $r['status'] }}">{{ __('dashboard.statuses.' . $r['status']) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-widgets::widget>
