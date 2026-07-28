<?php

namespace App\Enums;

enum UserRole: string
{
    case CUSTOMER = 'customer';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super_admin';
    case SYSTEM_ADMIN = 'system_admin';
    case SKIPPER = 'skipper';
    case B2B = 'b2b';

    public function label(): string
    {
        return match($this) {
            self::CUSTOMER => 'Cliente',
            self::ADMIN => 'Amministratore',
            self::SUPER_ADMIN => 'Super Amministratore',
            self::SYSTEM_ADMIN => 'System Admin (tecnico)',
            self::SKIPPER => 'Skipper (solo imbarco)',
            self::B2B => 'Agenzia (B2B)',
        };
    }

    /**
     * Accede all'area /admin.
     *
     * Include lo skipper, che però vede SOLO la sezione Imbarco: il gating fine
     * è nel middleware 'skipper_area' (vedi SkipperAreaMiddleware).
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPER_ADMIN, self::SYSTEM_ADMIN, self::SKIPPER]);
    }

    /**
     * Ruolo operativo di bordo: scansiona i QR dei biglietti all'imbarco e
     * nient'altro. Non vede prenotazioni, incassi, report o impostazioni.
     */
    public function isSkipper(): bool
    {
        return $this === self::SKIPPER;
    }

    /** Ruoli con pieno accesso al gestionale (tutto tranne lo skipper). */
    public function hasFullAdminAccess(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPER_ADMIN, self::SYSTEM_ADMIN]);
    }
}
