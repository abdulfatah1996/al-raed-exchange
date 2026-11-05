<?php

namespace Tests\Feature\Security;

use App\Support\Permissions;
use Tests\Feature\AlraedTestCase;

class HardeningTest extends AlraedTestCase
{
    public function test_site_and_panel_send_security_headers(): void
    {
        foreach (['/', route('filament.alraed.auth.login')] as $url) {
            $this->get($url)->assertOk()
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; object-src 'none'; form-action 'self'");
        }
    }

    public function test_hsts_is_only_sent_over_https(): void
    {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_production_defaults_to_secure_encrypted_session_cookies(): void
    {
        $this->app['env'] = 'production';
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';
        unset($_ENV['SESSION_SECURE_COOKIE'], $_SERVER['SESSION_SECURE_COOKIE'], $_ENV['SESSION_ENCRYPT'], $_SERVER['SESSION_ENCRYPT']);
        putenv('SESSION_SECURE_COOKIE'); putenv('SESSION_ENCRYPT');

        try {
            $config = require config_path('session.php');
            $this->assertTrue($config['secure']);
            $this->assertTrue($config['encrypt']);
            $this->assertTrue($config['http_only']);
            $this->assertSame('lax', $config['same_site']);
        } finally {
            putenv('APP_ENV=testing');
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
        }
    }

    public function test_tables_expand_rows_instead_of_scrolling_sideways(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN));
        $this->transfer();

        $this->get('/alraed/transfers')->assertOk()
            ->assertSee('alraed-row-summary', false)
            ->assertSee('alraed-details', false)
            ->assertSee('fi-ta-record-collapse-btn', false);
    }
}
