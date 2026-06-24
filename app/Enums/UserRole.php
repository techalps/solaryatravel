<?php

namespace App\Enums;

enum UserRole: string
{
    case CUSTOMER = 'customer';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super_admin';
    case SYSTEM_ADMIN = 'system_admin';

    public function label(): string
    {
        return match($this) {
            self::CUSTOMER => 'Cliente',
            self::ADMIN => 'Amministratore',
            self::SUPER_ADMIN => 'Super Amministratore',
            self::SYSTEM_ADMIN => 'System Admin (tecnico)',
        };
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPER_ADMIN, self::SYSTEM_ADMIN]);
    }
}
