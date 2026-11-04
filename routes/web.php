<?php

use App\Http\Controllers\PrivateFileController;
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

    // Only go "back" to a page on this site — never to a host taken from the Referer header.
    $previous = url()->previous();
    $sameHost = parse_url($previous, PHP_URL_HOST) === request()->getHost();

    return redirect()->to($sameHost ? $previous : route('home'));
})->middleware('throttle:30,1')->name('lang.switch');

Route::middleware(['auth', 'throttle:120,1'])
    ->get('/alraed/files/{path}', PrivateFileController::class)
    ->where('path', '.*')
    ->name('files.show');
