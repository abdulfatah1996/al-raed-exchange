<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(array_key_exists($locale, config('locales.supported')), 404);

    session(['locale' => $locale]);
    cookie()->queue('locale', $locale, 60 * 24 * 365);

    if ($user = request()->user()) {
        $user->forceFill(['locale' => $locale])->save();
    }

    return redirect()->back();
})->name('lang.switch');
