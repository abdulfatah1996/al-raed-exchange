@php $user = filament()->auth()->user(); @endphp
<div class="ar-sidebar-user">
    <div class="ar-sidebar-avatar">{{ mb_strtoupper(mb_substr($user?->name ?? 'A', 0, 1)) }}</div>
    <div class="ar-sidebar-user-info">
        <div class="ar-sidebar-user-name">{{ $user?->name }}</div>
        <div class="ar-sidebar-user-role">{{ __('dashboard.role_admin') }}</div>
    </div>
    <form method="POST" action="{{ filament()->getLogoutUrl() }}">
        @csrf
        <button type="submit" class="ar-sidebar-logout" title="{{ __('filament-panels::layout.actions.logout.label') }}">
            <x-filament::icon icon="heroicon-o-arrow-right-start-on-rectangle" style="width:1.1rem;height:1.1rem;" />
        </button>
    </form>
</div>
