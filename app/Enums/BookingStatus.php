<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case DEPOSIT_PAID = 'deposit_paid';
    case AWAITING_TRANSFER = 'awaiting_transfer';
    case CONFIRMED = 'confirmed';
    case CHECKED_IN = 'checked_in';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'In attesa',
            self::DEPOSIT_PAID => 'Acconto versato',
            self::AWAITING_TRANSFER => 'Attesa bonifico',
            self::CONFIRMED => 'Confermato',
            self::CHECKED_IN => 'Check-in effettuato',
            self::COMPLETED => 'Completato',
            self::CANCELLED => 'Cancellato',
            self::REFUNDED => 'Rimborsato',
            self::NO_SHOW => 'Non presentato',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::DEPOSIT_PAID => 'teal',
            self::AWAITING_TRANSFER => 'orange',
            self::CONFIRMED => 'green',
            self::CHECKED_IN => 'blue',
            self::COMPLETED => 'gray',
            self::CANCELLED => 'red',
            self::REFUNDED => 'purple',
            self::NO_SHOW => 'orange',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'clock',
            self::DEPOSIT_PAID => 'wallet',
            self::AWAITING_TRANSFER => 'building-columns',
            self::CONFIRMED => 'check-circle',
            self::CHECKED_IN => 'user-check',
            self::COMPLETED => 'flag',
            self::CANCELLED => 'x-circle',
            self::REFUNDED => 'arrow-uturn-left',
            self::NO_SHOW => 'user-x',
        };
    }

    public function canTransitionTo(BookingStatus $status): bool
    {
        return match($this) {
            self::PENDING => in_array($status, [self::DEPOSIT_PAID, self::AWAITING_TRANSFER, self::CONFIRMED, self::CANCELLED]),
            self::DEPOSIT_PAID => in_array($status, [self::CONFIRMED, self::CANCELLED, self::REFUNDED]),
            self::AWAITING_TRANSFER => in_array($status, [self::DEPOSIT_PAID, self::CONFIRMED, self::CANCELLED]),
            self::CONFIRMED => in_array($status, [self::CHECKED_IN, self::CANCELLED, self::REFUNDED]),
            self::CHECKED_IN => in_array($status, [self::COMPLETED, self::NO_SHOW]),
            self::COMPLETED => in_array($status, [self::REFUNDED]),
            self::CANCELLED => false,
            self::REFUNDED => false,
            self::NO_SHOW => false,
        };
    }
}
