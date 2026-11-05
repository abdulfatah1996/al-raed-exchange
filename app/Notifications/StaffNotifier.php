<?php

namespace App\Notifications;

use App\Enums\TransferStatus;
use App\Filament\Resources\TransferRequests\TransferRequestResource;
use App\Filament\Resources\Transfers\TransferResource;
use App\Models\Transfer;
use App\Models\TransferRequest;
use App\Models\User;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Sends in-panel (database) notifications to the staff who have to act next.
 *
 * Written synchronously with notifyNow(): Filament's database notification is
 * queued by default, and shared hosting usually runs no queue worker, so a
 * queued notification would never arrive.
 *
 * A failed notification must never block a financial operation, so every send
 * is wrapped and only reported.
 */
final class StaffNotifier
{
    private static bool $muted = false;

    /** Run $callback without sending notifications (bulk demo data, imports). */
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

    public static function newWebsiteRequest(TransferRequest $request): void
    {
        self::send(
            self::withPermission(Permissions::REQUESTS_MANAGE),
            'new_request',
            ['ref' => $request->reference, 'name' => $request->name],
            'heroicon-o-inbox-arrow-down', 'warning',
            TransferRequestResource::getUrl('index'),
        );
    }

    public static function transferStatusChanged(Transfer $transfer, TransferStatus $to): void
    {
        [$recipients, $key, $icon, $color] = match ($to) {
            TransferStatus::PendingVerification => [self::withPermission(Permissions::RECEIPTS_VERIFY), 'needs_verification', 'heroicon-o-shield-check', 'warning'],
            TransferStatus::Approved => [self::withPermission(Permissions::TRANSFERS_DELIVER), 'ready_to_deliver', 'heroicon-o-truck', 'success'],
            TransferStatus::Rejected, TransferStatus::OnHold => [self::owners($transfer), 'status_' . $to->value, 'heroicon-o-exclamation-triangle', 'danger'],
            TransferStatus::Delivered => [self::owners($transfer), 'delivered', 'heroicon-o-check-badge', 'success'],
            default => [collect(), null, null, null],
        };

        if ($key) {
            self::send($recipients, $key, ['ref' => $transfer->reference], $icon, $color, TransferResource::getUrl('view', ['record' => $transfer]));
        }
    }

    public static function transferAssigned(Transfer $transfer): void
    {
        $assignee = $transfer->assignedUser;
        if (! $assignee) {
            return;
        }

        self::send(collect([$assignee]), 'assigned', ['ref' => $transfer->reference], 'heroicon-o-user-circle', 'info',
            TransferResource::getUrl('view', ['record' => $transfer]));
    }

    /** Active staff holding a permission (directly or through a role). */
    public static function withPermission(string $permission): Collection
    {
        return User::query()->where('is_active', true)->permission($permission)->get();
    }

    /** The creator and the assignee of a transfer. */
    private static function owners(Transfer $transfer): Collection
    {
        return User::query()->where('is_active', true)
            ->whereIn('id', array_filter([$transfer->created_by, $transfer->assigned_user_id]))->get();
    }

    private static function send(Collection $recipients, string $key, array $params, string $icon, string $color, string $url): void
    {
        if (self::$muted) {
            return;
        }

        $actorId = auth()->id();

        foreach ($recipients->unique('id') as $user) {
            if ($user->id === $actorId) {
                continue; // nobody needs to be told about their own action
            }

            try {
                $locale = $user->locale ?: config('app.locale');
                $notification = Notification::make()
                    ->title(__("notifications.$key.title", $params, $locale))
                    ->body(__("notifications.$key.body", $params, $locale))
                    ->icon($icon)
                    ->iconColor($color)
                    ->actions([
                        Action::make('open')->label(__('notifications.open', [], $locale))->url($url)->markAsRead(),
                    ]);

                $user->notifyNow($notification->toDatabase());
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
