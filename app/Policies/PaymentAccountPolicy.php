<?php

namespace App\Policies;

use App\Models\PaymentAccount;
use App\Models\User;
use App\Support\Permissions as P;

/** Accounts are deactivated, never deleted, so past transfers keep their link. */
class PaymentAccountPolicy
{
    public function viewAny(User $user): bool { return $user->can(P::PAYMENT_ACCOUNTS_VIEW); }

    public function view(User $user, PaymentAccount $account): bool { return $user->can(P::PAYMENT_ACCOUNTS_VIEW); }

    public function create(User $user): bool { return $user->can(P::PAYMENT_ACCOUNTS_MANAGE); }

    public function update(User $user, PaymentAccount $account): bool { return $user->can(P::PAYMENT_ACCOUNTS_MANAGE); }

    public function delete(User $user, PaymentAccount $account): bool { return false; }

    public function deleteAny(User $user): bool { return false; }
}
