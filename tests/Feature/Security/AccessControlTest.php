<?php

namespace Tests\Feature\Security;

use App\Enums\TransferStatus;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\Feature\AlraedTestCase;

class AccessControlTest extends AlraedTestCase
{
    public function test_role_matrix(): void
    {
        $this->assertTrue(Role::findByName(Permissions::ROLE_ADMIN)->hasPermissionTo(Permissions::USERS_MANAGE));
        $this->assertFalse(Role::findByName(Permissions::ROLE_SUPERVISOR)->hasPermissionTo(Permissions::USERS_MANAGE));
        $this->assertFalse(Role::findByName(Permissions::ROLE_EMPLOYEE)->hasPermissionTo(Permissions::RECEIPTS_VERIFY));
        $this->assertFalse(Role::findByName(Permissions::ROLE_EMPLOYEE)->hasPermissionTo(Permissions::TRANSFERS_APPROVE));
        $this->assertFalse(Role::findByName(Permissions::ROLE_VIEWER)->hasPermissionTo(Permissions::FILES_VIEW));
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/alraed')->assertRedirect(route('filament.alraed.auth.login'));
    }

    public function test_account_without_role_is_refused(): void
    {
        $this->actingAs(User::factory()->create())->get('/alraed')->assertForbidden();
    }

    public function test_deactivated_admin_is_refused(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN, ['is_active' => false]))->get('/alraed')->assertForbidden();
    }

    public function test_staff_can_open_dashboard(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_EMPLOYEE))->get('/alraed')->assertOk();
    }

    public function test_two_factor_is_required_by_default(): void
    {
        // Routes get the "set up 2FA" middleware at boot when this is true (tests boot with it off).
        config(['alraed.require_mfa' => true]);
        $this->assertTrue(\Filament\Facades\Filament::getPanel('alraed')->isMultiFactorAuthenticationRequired());
    }

    public function test_viewer_cannot_open_customer_create_page(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_VIEWER))->get('/alraed/customers/create')->assertForbidden();
    }

    public function test_language_switch_never_redirects_off_site(): void
    {
        $this->get('/lang/en', ['referer' => 'https://evil.example/phish'])->assertRedirect(route('home'));
    }

    public function test_private_files_are_authorised_and_served_safely(): void
    {
        Storage::fake('local');
        $transfer = $this->transfer(TransferStatus::PendingTransfer);
        $jpg = UploadedFile::fake()->image('r.jpg')->storeAs('receipts', 'r.jpg', 'local');
        Storage::disk('local')->put('receipts/evil.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        Storage::disk('local')->put('receipts/orphan.jpg', 'x');
        $this->receipt($transfer, ['receipt_file' => $jpg]);
        $this->receipt($transfer, ['receipt_file' => 'receipts/evil.svg']);

        $this->get('/alraed/files/receipts/r.jpg')->assertRedirect(route('filament.alraed.auth.login'));
        $this->actingAs($this->staff(Permissions::ROLE_VIEWER))->get('/alraed/files/receipts/r.jpg')->assertForbidden();

        $employee = $this->staff(Permissions::ROLE_EMPLOYEE);
        $ok = $this->actingAs($employee)->get('/alraed/files/receipts/r.jpg')->assertOk();
        $ok->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline', (string) $ok->headers->get('Content-Disposition'));

        $svg = $this->actingAs($employee)->get('/alraed/files/receipts/evil.svg')->assertOk();
        $this->assertStringContainsString('attachment', (string) $svg->headers->get('Content-Disposition'));
        $this->assertStringContainsString('sandbox', (string) $svg->headers->get('Content-Security-Policy'));

        $this->actingAs($employee)->get('/alraed/files/receipts/orphan.jpg')->assertNotFound();
        $this->actingAs($employee)->get('/alraed/files/receipts/../.env')->assertNotFound();
        $this->actingAs($employee)->get('/alraed/files/other/r.jpg')->assertNotFound();
    }
}
