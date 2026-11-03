<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return __('site.login_heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('site.login_subheading');
    }
}
