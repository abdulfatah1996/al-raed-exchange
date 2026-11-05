<?php

namespace App\Filament\Resources\Users\Pages;

use App\Models\AuditLog;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $role = $data['role'];
        unset($data['role']);

        return DB::transaction(function () use ($data, $role) {
            $user = new User;
            $user->forceFill($data)->save(); // is_active is not mass-assignable from the outside world
            $user->syncRoles([$role]);
            AuditLog::record($user, 'role_changed', [], ['role' => $role]);

            return $user;
        });
    }
}
