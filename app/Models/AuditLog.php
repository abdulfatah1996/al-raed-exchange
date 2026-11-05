<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Append-only record of who changed what: create / update / delete on money and
 * access-control models, plus role and permission changes.
 *
 * Secret columns (passwords, 2FA, encrypted account details) are never copied:
 * the log only notes that they changed.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const MASK = '••• (changed)';

    /** Columns whose values must never be written to the log. */
    public const SECRET = [
        'password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes',
        'details', 'beneficiary_details', 'idempotency_key',
    ];

    /** Bookkeeping columns not worth a log line. */
    public const IGNORED = ['updated_at', 'created_at'];

    private static bool $muted = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $log) => $log->created_at ??= now());
        static::updating(fn () => throw new LogicException('Audit log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit log entries cannot be deleted.'));
    }

    /** Run $callback without writing audit entries (bulk demo data). */
    public static function muted(callable $callback): mixed
    {
        $previous = self::$muted;
        self::$muted = true;
        try {
            return $callback();
        } finally {
            self::$muted = $previous;
        }
    }

    public static function record(Model $subject, string $event, array $old = [], array $new = []): ?self
    {
        if (self::$muted) {
            return null;
        }

        $old = self::clean($old);
        $new = self::clean($new);
        if ($event === 'updated' && $old === [] && $new === []) {
            return null;
        }

        return self::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'subject_label' => self::labelFor($subject),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    private static function clean(array $values): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            if (in_array($key, self::IGNORED, true)) {
                continue;
            }
            if (in_array($key, self::SECRET, true)) {
                $out[$key] = self::MASK;
                continue;
            }
            $out[$key] = $value instanceof \BackedEnum ? $value->value : $value;
        }

        return $out;
    }

    private static function labelFor(Model $subject): string
    {
        foreach (['reference', 'code', 'internal_name', 'email', 'title_ar', 'name'] as $attr) {
            if (filled($subject->getAttribute($attr))) {
                return (string) $subject->getAttribute($attr);
            }
        }

        return class_basename($subject) . ' #' . $subject->getKey();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }
}
