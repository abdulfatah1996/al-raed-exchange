<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Permissions as P;

/** The audit log is read-only for everyone, and visible only to user administrators. */
class AuditLogPolicy
{
    public function viewAny(User $user): bool { return $user->can(P::USERS_MANAGE); }
    public function view(User $user, AuditLog $log): bool { return $user->can(P::USERS_MANAGE); }
    public function create(User $user): bool { return false; }
    public function update(User $user, AuditLog $log): bool { return false; }
    public function delete(User $user, AuditLog $log): bool { return false; }
    public function deleteAny(User $user): bool { return false; }
}
