<x-filament-widgets::widget>
    <div class="ar-card">
        <div class="ar-card-head">
            <h3>{{ __('dashboard.recent') }}</h3>
            <a href="{{ \App\Filament\Resources\Transfers\TransferResource::getUrl('index') }}" class="ar-card-link">{{ __('dashboard.view_all') }}</a>
        </div>
        @if (empty($rows))
            <p class="ar-empty">{{ __('dashboard.empty_recent') }}</p>
        @else
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
                            <td><a href="{{ $r['url'] }}" class="ar-ref">{{ $r['ref'] }}</a></td>
                            <td>{{ $r['customer'] }}</td>
                            <td><span dir="ltr">{{ $r['amount'] }}</span></td>
                            <td><span class="ar-badge ar-badge--{{ $r['status'] }}">{{ $r['status_label'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</x-filament-widgets::widget>
