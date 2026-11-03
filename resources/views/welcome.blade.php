<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('locales.supported.' . app()->getLocale() . '.dir', 'rtl') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('site.brand') }} — {{ __('site.tagline') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --navy: #0B1F3A; --navy-2: #122B4F; --gold: #C9A227; --white: #FFFFFF; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               background: radial-gradient(1200px 600px at 50% -10%, var(--navy-2), var(--navy));
               color: var(--white); font-family: 'Cairo', system-ui, sans-serif; text-align: center; padding: 24px; }
        .card { max-width: 560px; width: 100%; }
        .brand { font-size: clamp(28px, 6vw, 44px); font-weight: 700; color: var(--gold); margin: 0 0 8px; }
        .tagline { font-size: clamp(16px, 3vw, 20px); opacity: .9; margin: 0 0 32px; }
        .soon { display: inline-block; padding: 8px 18px; border: 1px solid rgba(201,162,39,.5); border-radius: 999px; font-size: 14px; margin-bottom: 32px; }
        .btn { display: inline-block; background: var(--gold); color: var(--navy); text-decoration: none; font-weight: 700;
               padding: 14px 28px; border-radius: 12px; font-size: 16px; }
        .lang { position: fixed; top: 16px; inset-inline-end: 16px; color: var(--gold); text-decoration: none; font-weight: 600;
                border: 1px solid rgba(201,162,39,.5); padding: 6px 14px; border-radius: 999px; font-size: 14px; }
    </style>
</head>
<body>
    <a class="lang" href="{{ route('lang.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}">{{ __('site.switch_language') }}</a>
    <main class="card">
        <h1 class="brand">{{ __('site.brand') }}</h1>
        <p class="tagline">{{ __('site.tagline') }}</p>
        <span class="soon">{{ __('site.coming_soon') }}</span>
        <div><a class="btn" href="https://wa.me/970597775080" target="_blank" rel="noopener">{{ __('site.whatsapp') }}</a></div>
    </main>
</body>
</html>
