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

Route::middleware(['auth'])->get('/alraed/files/{path}', function (string $path) {
    abort_unless(\Illuminate\Support\Str::startsWith($path, ['receipts/', 'proofs/']), 403);
    abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

    return \Illuminate\Support\Facades\Storage::disk('local')->response($path);
})->where('path', '.*')->name('files.show');
