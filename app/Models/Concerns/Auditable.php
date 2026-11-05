<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/** Writes an AuditLog entry on create, update, delete and restore. */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $m) => AuditLog::record($m, 'created', [], $m->getAttributes()));

        static::updated(function (Model $m) {
            $changes = $m->getChanges();
            $old = array_intersect_key($m->getRawOriginal(), $changes);
            AuditLog::record($m, 'updated', $old, $changes);
        });

        static::deleted(fn (Model $m) => AuditLog::record($m, 'deleted', $m->getRawOriginal(), []));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $m) => AuditLog::record($m, 'restored'));
        }
    }
}
