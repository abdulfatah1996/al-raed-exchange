<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Support\Permissions as P;

class CustomerPolicy
{
    public function viewAny(User $user): bool { return $user->can(P::CUSTOMERS_VIEW); }

    public function view(User $user, Customer $customer): bool { return $user->can(P::CUSTOMERS_VIEW); }

    public function create(User $user): bool { return $user->can(P::CUSTOMERS_MANAGE); }

    public function update(User $user, Customer $customer): bool { return $user->can(P::CUSTOMERS_MANAGE); }

    public function delete(User $user, Customer $customer): bool { return $user->can(P::CUSTOMERS_DELETE); }

    public function deleteAny(User $user): bool { return false; }

    public function restore(User $user, Customer $customer): bool { return $user->can(P::CUSTOMERS_DELETE); }

    public function restoreAny(User $user): bool { return false; }

    public function forceDelete(User $user, Customer $customer): bool { return false; }

    public function forceDeleteAny(User $user): bool { return false; }
}
