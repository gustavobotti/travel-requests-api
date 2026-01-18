<?php

namespace App\Enums;

enum TravelRequestStatus: string
{
    case REQUESTED = 'REQUESTED';
    case APPROVED = 'APPROVED';
    case CANCELLED = 'CANCELLED';

    /**
     * Get all available status values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all available status names as an array.
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::REQUESTED => 'Requested',
            self::APPROVED => 'Approved',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if the status is requested.
     */
    public function isRequested(): bool
    {
        return $this === self::REQUESTED;
    }

    /**
     * Check if the status is approved.
     */
    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Check if the status is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    /**
     * Check if the status can be cancelled.
     * Business logic: can cancel if status is REQUESTED or APPROVED
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [self::REQUESTED, self::APPROVED]);
    }

    /**
     * Check if the status can be approved.
     * Business logic: can only approve if status is REQUESTED
     */
    public function canBeApproved(): bool
    {
        return $this === self::REQUESTED;
    }
}
