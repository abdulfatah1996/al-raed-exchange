<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\Permissions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->getRecord()->getRoleNames()->first();

        return $data;
    }

    /** @param User $record */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // "role" and "is_active" are absent when the fields are disabled (editing yourself).
        $role = $data['role'] ?? null;
        unset($data['role']);

        if ($role && $role !== Permissions::ROLE_ADMIN && $record->isLastActiveAdmin()) {
            Notification::make()->title(__('admin.users.last_admin'))->danger()->send();
            throw new Halt;
        }

        try {
            DB::transaction(function () use ($record, $data, $role) {
                $record->forceFill($data)->save();
                if ($role) {
                    $record->syncRoles([$role]);
                }
            });
        } catch (RuntimeException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            throw new Halt;
        }

        return $record;
    }
}
