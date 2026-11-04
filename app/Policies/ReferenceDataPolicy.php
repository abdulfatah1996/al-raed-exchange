<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions as P;
use Illuminate\Database\Eloquent\Model;

/** Countries, currencies, crypto networks, services and delivery methods. */
class ReferenceDataPolicy
{
    public function viewAny(User $user): bool { return $user->can(P::PANEL_ACCESS); }

    public function view(User $user, Model $model): bool { return $user->can(P::PANEL_ACCESS); }

    public function create(User $user): bool { return $user->can(P::SETTINGS_MANAGE); }

    public function update(User $user, Model $model): bool { return $user->can(P::SETTINGS_MANAGE); }

    public function delete(User $user, Model $model): bool { return false; }

    public function deleteAny(User $user): bool { return false; }
}
