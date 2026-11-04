<?php

namespace App\Policies;

use App\Enums\ReceiptVerification;
use App\Models\Receipt;
use App\Models\User;
use App\Support\Permissions as P;

class ReceiptPolicy
{
    public function viewAny(User $user): bool { return $user->can(P::TRANSFERS_VIEW); }

    public function view(User $user, Receipt $receipt): bool { return $user->can(P::TRANSFERS_VIEW); }

    public function create(User $user): bool { return $user->can(P::RECEIPTS_MANAGE); }

    public function update(User $user, Receipt $receipt): bool
    {
        return $user->can(P::RECEIPTS_MANAGE)
            && $receipt->verification_status !== ReceiptVerification::Verified
            && (bool) $receipt->transfer?->acceptsReceipts();
    }

    public function delete(User $user, Receipt $receipt): bool { return false; }

    public function deleteAny(User $user): bool { return false; }

    public function restore(User $user, Receipt $receipt): bool { return false; }

    public function forceDelete(User $user, Receipt $receipt): bool { return false; }

    public function forceDeleteAny(User $user): bool { return false; }

    public function verify(User $user, Receipt $receipt): bool
    {
        return $user->can(P::RECEIPTS_VERIFY) && $receipt->canBeVerified();
    }

    public function reject(User $user, Receipt $receipt): bool
    {
        return $this->verify($user, $receipt);
    }

    public function deliver(User $user, Receipt $receipt): bool
    {
        return $user->can(P::RECEIPTS_DELIVER) && $receipt->canBeDelivered();
    }
}
