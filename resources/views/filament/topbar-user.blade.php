@php
    $user = auth()->user();
    $role = $user?->getRoleNames()->first();
@endphp
@if ($user)
<div class="ar-topbar-user">
    <span class="ar-topbar-user-name">{{ $user->name }}</span>
    @if ($role)<span class="ar-topbar-user-role">{{ __("dashboard.roles.$role") }}</span>@endif
</div>
@endif
