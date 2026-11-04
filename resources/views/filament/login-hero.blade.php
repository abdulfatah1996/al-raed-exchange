{{-- Brand side of the sign-in screens (login, two-factor). --}}
<aside class="ar-auth-aside" aria-hidden="false">
    <div class="ar-auth-aside-inner">
        <a href="{{ route('site.home') }}" class="ar-auth-logo">
            @include('site.partials.logo', ['dark' => true, 'size' => 'ar-auth-logo-mark'])
        </a>
        <div class="ar-auth-copy">
            <p class="ar-auth-kicker">{{ __('site.tagline') }}</p>
            <h2 class="ar-auth-title">{{ __('site.system_name') }}</h2>
            <ul class="ar-auth-points">
                @foreach (__('dashboard.auth_points') as $point)
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 4.6-3 8.5-7 10-4-1.5-7-5.4-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>
                        <span>{{ $point }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <p class="ar-auth-foot">© {{ now()->year }} {{ __('site.brand') }}</p>
    </div>
</aside>
