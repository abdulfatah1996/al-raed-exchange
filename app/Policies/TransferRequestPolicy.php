<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\TransferRequest;
use App\Models\User;
use App\Support\Permissions as P;

/** Website requests are created by customers only; staff review, convert or close them. */
class TransferRequestPolicy
{
    public function viewAny(User $user): bool { return $user->can(P::REQUESTS_VIEW); }

    public function view(User $user, TransferRequest $request): bool { return $user->can(P::REQUESTS_VIEW); }

    public function create(User $user): bool { return false; }

    public function update(User $user, TransferRequest $request): bool { return false; }

    public function delete(User $user, TransferRequest $request): bool { return false; }

    public function deleteAny(User $user): bool { return false; }

    public function handle(User $user, TransferRequest $request): bool
    {
        return $user->can(P::REQUESTS_MANAGE) && in_array($request->status, [RequestStatus::New, RequestStatus::Contacted], true);
    }

    public function convert(User $user, TransferRequest $request): bool
    {
        return $this->handle($user, $request) && $user->can(P::TRANSFERS_CREATE) && $request->transfer_id === null;
    }
}
