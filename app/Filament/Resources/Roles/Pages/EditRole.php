<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Support\Permissions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('dashboard.roles.' . $this->getRecord()->name);
    }

    public function getSubheading(): ?string
    {
        return __('admin.roles.descriptions.' . $this->getRecord()->name);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $owned = $this->getRecord()->permissions->pluck('name')->all();

        foreach (Permissions::grouped() as $group => $permissions) {
            $data["permissions_{$group}"] = array_values(array_intersect($permissions, $owned));
        }

        return $data;
    }

    /** @param Role $record */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $selected = collect(Permissions::grouped())->keys()
            ->flatMap(fn (string $group) => $data["permissions_{$group}"] ?? [])
            ->intersect(Permissions::all())   // ignore anything not in the catalogue
            ->values()->all();

        $record->syncPermissions($selected);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
