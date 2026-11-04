<?php

namespace App\Support;

/**
 * Single source of truth for permission names and the default role matrix.
 *
 * Roles are assigned to staff accounts; never check role names in code —
 * always check a permission (via policies or $user->can()).
 */
final class Permissions
{
    public const PANEL_ACCESS = 'panel.access';

    public const CUSTOMERS_VIEW = 'customers.view';
    public const CUSTOMERS_MANAGE = 'customers.manage';
    public const CUSTOMERS_DELETE = 'customers.delete';

    public const TRANSFERS_VIEW = 'transfers.view';
    public const TRANSFERS_CREATE = 'transfers.create';
    public const TRANSFERS_UPDATE = 'transfers.update';
    public const TRANSFERS_PRICE = 'transfers.price';
    public const TRANSFERS_APPROVE = 'transfers.approve';
    public const TRANSFERS_DELIVER = 'transfers.deliver';
    public const TRANSFERS_CANCEL = 'transfers.cancel';
    public const TRANSFERS_DELETE = 'transfers.delete';

    public const RECEIPTS_MANAGE = 'receipts.manage';
    public const RECEIPTS_VERIFY = 'receipts.verify';
    public const RECEIPTS_DELIVER = 'receipts.deliver';

    public const PAYMENT_ACCOUNTS_VIEW = 'payment_accounts.view';
    public const PAYMENT_ACCOUNTS_MANAGE = 'payment_accounts.manage';

    public const SETTINGS_MANAGE = 'settings.manage';
    public const FILES_VIEW = 'files.view';
    public const USERS_MANAGE = 'users.manage';

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_EMPLOYEE = 'employee';
    public const ROLE_CASHIER = 'cashier';
    public const ROLE_VIEWER = 'viewer';

    /** @return list<string> */
    public static function all(): array
    {
        $constants = (new \ReflectionClass(self::class))->getConstants(\ReflectionClassConstant::IS_PUBLIC);

        return array_values(array_filter($constants, fn ($v, $k) => ! str_starts_with($k, 'ROLE_'), ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Default role → permissions matrix.
     * Verification and approval belong to the supervisor only, so the employee
     * who records a receipt cannot also approve it (maker / checker).
     *
     * @return array<string, list<string>>
     */
    public static function roles(): array
    {
        $viewer = [self::PANEL_ACCESS, self::CUSTOMERS_VIEW, self::TRANSFERS_VIEW, self::PAYMENT_ACCOUNTS_VIEW];

        return [
            self::ROLE_ADMIN => self::all(),
            self::ROLE_SUPERVISOR => array_values(array_diff(self::all(), [self::USERS_MANAGE])),
            self::ROLE_EMPLOYEE => [
                ...$viewer, self::CUSTOMERS_MANAGE, self::TRANSFERS_CREATE, self::TRANSFERS_UPDATE,
                self::TRANSFERS_PRICE, self::TRANSFERS_CANCEL, self::RECEIPTS_MANAGE, self::FILES_VIEW,
            ],
            self::ROLE_CASHIER => [...$viewer, self::TRANSFERS_DELIVER, self::RECEIPTS_DELIVER, self::FILES_VIEW],
            self::ROLE_VIEWER => $viewer,
        ];
    }
}
