<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions as P;

/** Staff accounts are deactivated, never deleted: transfers keep pointing at who did what. */
class UserPolicy
{
    public function viewAny(User $user): bool { return $user->can(P::USERS_MANAGE); }

    public function view(User $user, User $model): bool { return $user->can(P::USERS_MANAGE); }

    public function create(User $user): bool { return $user->can(P::USERS_MANAGE); }

    public function update(User $user, User $model): bool { return $user->can(P::USERS_MANAGE); }

    public function delete(User $user, User $model): bool { return false; }

    public function deleteAny(User $user): bool { return false; }

    /** Nobody can switch off their own account. */
    public function toggleActive(User $user, User $model): bool
    {
        return $user->can(P::USERS_MANAGE) && ! $user->is($model);
    }

    public function resetMfa(User $user, User $model): bool
    {
        return $user->can(P::USERS_MANAGE) && ! $user->is($model) && $model->hasAppAuthentication();
    }
}
