<?php

namespace App\Policies;

use App\Models\TransferStatusLog;
use App\Models\User;
use App\Support\Permissions as P;

/** The status history is append-only and written by the system only. */
class TransferStatusLogPolicy
{
    public function viewAny(User $user): bool { return $user->can(P::TRANSFERS_VIEW); }

    public function view(User $user, TransferStatusLog $log): bool { return $user->can(P::TRANSFERS_VIEW); }

    public function create(User $user): bool { return false; }

    public function update(User $user, TransferStatusLog $log): bool { return false; }

    public function delete(User $user, TransferStatusLog $log): bool { return false; }

    public function deleteAny(User $user): bool { return false; }
}
