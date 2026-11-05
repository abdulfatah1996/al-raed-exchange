<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent and safe to run on every deploy. Never creates users or passwords.
 *
 * - Missing permissions are created.
 * - A role created for the first time gets its default permissions.
 * - An existing role keeps whatever an administrator set in the panel; it only
 *   receives permissions that are brand new in this run and that the default
 *   matrix assigns to it.
 * - The admin role always holds every permission.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $new = [];
        foreach (Permissions::all() as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            if ($permission->wasRecentlyCreated) {
                $new[] = $name;
            }
        }

        foreach (Permissions::roles() as $name => $defaults) {
            $role = Role::findOrCreate($name, 'web');

            if ($name === Permissions::ROLE_ADMIN) {
                $role->syncPermissions(Permissions::all());
            } elseif ($role->wasRecentlyCreated) {
                $role->syncPermissions($defaults);
            } elseif ($added = array_values(array_intersect($defaults, $new))) {
                $role->givePermissionTo($added);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
