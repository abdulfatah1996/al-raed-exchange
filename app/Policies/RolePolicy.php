<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions as P;
use Spatie\Permission\Models\Role;

/** The role set is fixed; only the permissions of non-admin roles can be changed. */
class RolePolicy
{
    public function viewAny(User $user): bool { return $user->can(P::USERS_MANAGE); }

    public function view(User $user, Role $role): bool { return $user->can(P::USERS_MANAGE); }

    public function create(User $user): bool { return false; }

    public function update(User $user, Role $role): bool
    {
        return $user->can(P::USERS_MANAGE) && $role->name !== P::ROLE_ADMIN;
    }

    public function delete(User $user, Role $role): bool { return false; }

    public function deleteAny(User $user): bool { return false; }
}
