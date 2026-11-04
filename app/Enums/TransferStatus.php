<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TransferStatus: string implements HasLabel, HasColor
{
    case New = 'new';
    case PendingPricing = 'pending_pricing';
    case PendingCustomerApproval = 'pending_customer_approval';
    case PendingTransfer = 'pending_transfer';
    case PendingVerification = 'pending_verification';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case OnHold = 'on_hold';

    public function getLabel(): string
    {
        return __('enums.transfer_status.' . $this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::PendingPricing, self::PendingCustomerApproval, self::PendingTransfer => 'info',
            self::PendingVerification, self::UnderReview => 'warning',
            self::Approved, self::InProgress => 'primary',
            self::Delivered => 'success',
            self::Cancelled, self::Rejected => 'danger',
            self::OnHold => 'gray',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled], true);
    }

    /** Once approved (or frozen/closed), amounts, rates, parties and receipts can no longer change. */
    public function isFinanciallyLocked(): bool
    {
        return in_array($this, [self::Approved, self::InProgress, self::Delivered, self::Cancelled, self::OnHold], true);
    }

    /** Receipts may be verified only while the transfer is in the checking stage. */
    public function allowsReceiptVerification(): bool
    {
        return in_array($this, [self::PendingVerification, self::UnderReview], true);
    }

    /** Receipts may be paid out only after approval. */
    public function allowsReceiptDelivery(): bool
    {
        return in_array($this, [self::Approved, self::InProgress], true);
    }

    /** Permission a user needs to move a transfer INTO this status. */
    public function requiredPermission(): string
    {
        return match ($this) {
            self::PendingCustomerApproval => \App\Support\Permissions::TRANSFERS_PRICE,
            self::Approved => \App\Support\Permissions::TRANSFERS_APPROVE,
            self::Rejected => \App\Support\Permissions::RECEIPTS_VERIFY,
            self::InProgress, self::Delivered => \App\Support\Permissions::TRANSFERS_DELIVER,
            self::Cancelled => \App\Support\Permissions::TRANSFERS_CANCEL,
            default => \App\Support\Permissions::TRANSFERS_UPDATE,
        };
    }

    /**
     * Allowed next states — the single source of truth for the state machine.
     * OnHold may only return to the status it was held from (see Transfer::allowedNextStatuses())
     * or be cancelled; it can never be used to skip pricing or verification.
     */
    public function next(): array
    {
        return match ($this) {
            self::New => [self::PendingPricing, self::Cancelled, self::OnHold],
            self::PendingPricing => [self::PendingCustomerApproval, self::Cancelled, self::OnHold],
            self::PendingCustomerApproval => [self::PendingTransfer, self::PendingPricing, self::Cancelled, self::Rejected, self::OnHold],
            self::PendingTransfer => [self::PendingVerification, self::Cancelled, self::OnHold],
            self::PendingVerification => [self::UnderReview, self::Rejected, self::OnHold],
            self::UnderReview => [self::Approved, self::Rejected, self::PendingVerification, self::OnHold],
            self::Approved => [self::InProgress, self::OnHold, self::Cancelled],
            self::InProgress => [self::Delivered, self::OnHold],
            self::Rejected => [self::PendingVerification, self::Cancelled],
            self::OnHold => [self::Cancelled],
            self::Delivered, self::Cancelled => [],
        };
    }
}
