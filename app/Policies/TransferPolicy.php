<?php

namespace App\Policies;

use App\Enums\TransferStatus;
use App\Models\Transfer;
use App\Models\User;
use App\Support\Permissions as P;

class TransferPolicy
{
    public function viewAny(User $user): bool { return $user->can(P::TRANSFERS_VIEW); }

    public function view(User $user, Transfer $transfer): bool { return $user->can(P::TRANSFERS_VIEW); }

    public function create(User $user): bool { return $user->can(P::TRANSFERS_CREATE); }

    /** Closed transfers are read-only; locked ones only accept notes, assignment and the delivery proof. */
    public function update(User $user, Transfer $transfer): bool
    {
        return $user->can(P::TRANSFERS_UPDATE) && ! $transfer->status->isTerminal();
    }

    /** Only a new or cancelled transfer may be (soft) deleted. */
    public function delete(User $user, Transfer $transfer): bool
    {
        return $user->can(P::TRANSFERS_DELETE) && in_array($transfer->status, [TransferStatus::New, TransferStatus::Cancelled], true);
    }

    public function deleteAny(User $user): bool { return false; }

    public function restore(User $user, Transfer $transfer): bool { return $user->can(P::TRANSFERS_DELETE); }

    public function forceDelete(User $user, Transfer $transfer): bool { return false; }

    public function forceDeleteAny(User $user): bool { return false; }

    /** May this user move the transfer into $to right now? */
    public function transition(User $user, Transfer $transfer, TransferStatus $to): bool
    {
        return in_array($to, $transfer->allowedNextStatuses(), true) && $user->can($to->requiredPermission());
    }
}
