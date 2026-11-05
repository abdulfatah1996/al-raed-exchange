<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Feature\AlraedTestCase;

class UserManagementTest extends AlraedTestCase
{
    public function test_only_admins_reach_users_and_roles(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN))->get('/alraed/users')->assertOk();
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN))->get('/alraed/roles')->assertOk();
        $this->actingAs($this->staff(Permissions::ROLE_SUPERVISOR))->get('/alraed/users')->assertForbidden();
        $this->actingAs($this->staff(Permissions::ROLE_SUPERVISOR))->get('/alraed/roles')->assertForbidden();
    }

    public function test_admin_creates_a_staff_account_with_a_role(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN));

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Cashier One', 'email' => 'Cashier@Alraed.TEST', 'password' => 'Strong-pass-2026', 'password_confirmation' => 'Strong-pass-2026', 'role' => 'cashier', 'locale' => 'ar', 'is_active' => true])
            ->call('create')->assertHasNoFormErrors();

        $user = User::where('email', 'cashier@alraed.test')->sole();
        $this->assertTrue($user->hasRole('cashier'));
        $this->assertTrue(Hash::check('Strong-pass-2026', $user->password));
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN));

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'X', 'email' => 'x@alraed.test', 'password' => 'short1', 'password_confirmation' => 'short1', 'role' => 'viewer'])
            ->call('create')->assertHasFormErrors(['password']);
    }

    public function test_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $admin = $this->staff(Permissions::ROLE_ADMIN);
        $this->actingAs($admin);

        $this->assertFalse($admin->can('toggleActive', $admin));

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->assertFormFieldIsDisabled('role')
            ->assertFormFieldIsDisabled('is_active')
            ->fillForm(['name' => 'Renamed'])->call('save')->assertHasNoFormErrors();

        $this->assertTrue($admin->fresh()->hasRole('admin'));
        $this->assertSame('Renamed', $admin->fresh()->name);
    }

    public function test_last_active_admin_cannot_be_deactivated(): void
    {
        $admin = $this->staff(Permissions::ROLE_ADMIN);

        $this->expectException(\RuntimeException::class);
        $admin->update(['is_active' => false]);
    }

    public function test_password_change_is_optional_on_edit(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_ADMIN));
        $employee = $this->staff(Permissions::ROLE_EMPLOYEE, ['password' => 'Original-pass-2026']);

        Livewire::test(EditUser::class, ['record' => $employee->getRouteKey()])
            ->fillForm(['role' => 'supervisor'])->call('save')->assertHasNoFormErrors();

        $employee->refresh();
        $this->assertTrue($employee->hasRole('supervisor') && ! $employee->hasRole('employee'));
        $this->assertTrue(Hash::check('Original-pass-2026', $employee->password));
    }

    public function test_role_permissions_are_editable_except_admin(): void
    {
        $admin = $this->staff(Permissions::ROLE_ADMIN);
        $this->actingAs($admin);
        $this->assertFalse($admin->can('update', Role::findByName('admin')));

        $cashier = Role::findByName('cashier');
        Livewire::test(EditRole::class, ['record' => $cashier->getRouteKey()])
            ->fillForm(['permissions_requests' => ['requests.view', 'requests.manage']])
            ->call('save')->assertHasNoFormErrors();

        $this->assertTrue($cashier->fresh()->hasPermissionTo('requests.manage'));
        $this->assertTrue($cashier->fresh()->hasPermissionTo('transfers.deliver'), 'other groups untouched');
    }

    public function test_seeder_does_not_undo_admin_edits(): void
    {
        Role::findByName('cashier')->revokePermissionTo('files.view');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertFalse(Role::findByName('cashier')->fresh()->hasPermissionTo('files.view'));
        $this->assertCount(count(Permissions::all()), Role::findByName('admin')->permissions);
    }

    public function test_every_permission_has_a_label_in_both_languages(): void
    {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);
            foreach (Permissions::all() as $permission) {
                $this->assertNotSame($permission, Permissions::label($permission), "$locale: $permission");
            }
        }
    }
}
