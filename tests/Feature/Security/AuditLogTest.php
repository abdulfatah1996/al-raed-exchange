<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Support\Permissions;
use LogicException;
use Tests\Feature\AlraedTestCase;

class AuditLogTest extends AlraedTestCase
{
    public function test_edits_are_logged_with_who_and_what_changed(): void
    {
        $admin = $this->staff(Permissions::ROLE_ADMIN);
        $this->actingAs($admin);
        $transfer = $this->transfer();

        $transfer->update(['send_amount' => 999]);

        $log = AuditLog::where('auditable_id', $transfer->id)->where('event', 'updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertEquals(999, $log->new_values['send_amount']);
        $this->assertArrayHasKey('send_amount', $log->old_values);
        $this->assertSame($transfer->reference, $log->subject_label);
    }

    public function test_secrets_are_never_copied_into_the_log(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN));
        $user = $this->staff(Permissions::ROLE_EMPLOYEE);

        $user->forceFill(['password' => 'Another-Secret-123'])->save();
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        $json = AuditLog::where('auditable_id', $user->id)->get()->toJson();
        $this->assertStringNotContainsString('Another-Secret-123', $json);
        $this->assertStringNotContainsString('JBSWY3DPEHPK3PXP', $json);
        $this->assertStringNotContainsString('$2y$', $json, 'password hash must not be logged either');
        $this->assertStringContainsString('changed', $json);
    }

    public function test_entries_cannot_be_changed_or_deleted(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN));
        Customer::create(['name' => 'X', 'phone' => '+970599000001']);
        $log = AuditLog::firstOrFail();

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_only_user_administrators_can_open_the_audit_log(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_SUPERVISOR))->get('/alraed/audit-logs')->assertForbidden();
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN))->get('/alraed/audit-logs')->assertOk();
    }
}
