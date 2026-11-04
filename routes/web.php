<?php

use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\Site\SiteController;
use App\Http\Controllers\Site\TransferRequestController;
use Illuminate\Support\Facades\Route;

// ── Public website ─────────────────────────────────────────────
Route::controller(SiteController::class)->name('site.')->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/services', 'services')->name('services');
    Route::get('/announcements', 'announcements')->name('announcements');
    Route::get('/contact', 'contact')->name('contact');
    Route::get('/page/{slug}', 'page')->name('page');
});

Route::controller(TransferRequestController::class)->name('site.')->group(function () {
    Route::get('/request', 'create')->name('request');
    Route::post('/request', 'store')->middleware('throttle:5,10')->name('request.store');
    Route::get('/request/done', 'done')->name('request.done');
    Route::get('/track', 'trackForm')->name('track');
    Route::post('/track', 'track')->middleware('throttle:10,1')->name('track.lookup');
});


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

    return redirect()->to($sameHost ? $previous : route('site.home'));
})->middleware('throttle:30,1')->name('lang.switch');

// ── Staff-only files ───────────────────────────────────────────
Route::middleware(['auth', 'throttle:120,1'])
    ->get('/alraed/files/{path}', PrivateFileController::class)
    ->where('path', '.*')
    ->name('files.show');
