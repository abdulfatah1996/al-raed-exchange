<?php

namespace App\Policies;

use App\Models\CustomerNote;
use App\Models\User;
use App\Support\Permissions as P;

/** Follow-up notes are an audit trail: they can be added, never edited or removed. */
class CustomerNotePolicy
{
    public function viewAny(User $user): bool { return $user->can(P::CUSTOMERS_VIEW); }

    public function view(User $user, CustomerNote $note): bool { return $user->can(P::CUSTOMERS_VIEW); }

    public function create(User $user): bool { return $user->can(P::CUSTOMERS_MANAGE); }

    public function update(User $user, CustomerNote $note): bool { return false; }

    public function delete(User $user, CustomerNote $note): bool { return false; }

    public function deleteAny(User $user): bool { return false; }
}
