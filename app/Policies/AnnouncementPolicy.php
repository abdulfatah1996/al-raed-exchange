<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use App\Support\Permissions as P;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool { return $user->can(P::PANEL_ACCESS); }

    public function view(User $user, Announcement $a): bool { return $user->can(P::PANEL_ACCESS); }

    public function create(User $user): bool { return $user->can(P::ANNOUNCEMENTS_MANAGE); }

    public function update(User $user, Announcement $a): bool { return $user->can(P::ANNOUNCEMENTS_MANAGE); }

    public function delete(User $user, Announcement $a): bool { return $user->can(P::ANNOUNCEMENTS_MANAGE); }

    public function deleteAny(User $user): bool { return $user->can(P::ANNOUNCEMENTS_MANAGE); }
}
