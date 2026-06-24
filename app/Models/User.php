<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'tax_code',
        'locale',
        'date_of_birth',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
        ];
    }

    /**
     * Normalizza il codice fiscale in maiuscolo (vuoto → null), a prescindere
     * dal punto di ingresso (prenotazione, profilo, admin).
     */
    protected function taxCode(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn ($value) => filled($value) ? strtoupper(trim($value)) : null,
        );
    }

    /**
     * Check if the user is an admin (accede all'area admin).
     * Include system_admin: ha tutti i poteri admin più la sezione Sistema.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin', 'system_admin']);
    }

    /**
     * Check if the user is a super admin (poteri gestionali completi).
     * NB: il system_admin ha gli stessi poteri gestionali — vedi hasSuperAdminPowers().
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Ruolo tecnico: vede Sistema (log, deploy, migrazioni), che il super_admin non vede.
     */
    public function isSystemAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    /**
     * Ha i poteri gestionali da super admin? Vero sia per super_admin sia per
     * system_admin (il tecnico può fare tutto ciò che fa il super admin).
     * Usare questo al posto di isSuperAdmin() per gating dei poteri gestionali.
     */
    public function hasSuperAdminPowers(): bool
    {
        return in_array($this->role, ['super_admin', 'system_admin']);
    }

    /**
     * Check if the user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * Get the user's bookings.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Sovrascrive l'invio standard della mail di verifica:
     * spediamo la nostra UserWelcome che include il link signed.
     * Chiamato sia dal listener Registered che dal pulsante "Reinvia mail di verifica".
     */
    public function sendEmailVerificationNotification(): void
    {
        \Illuminate\Support\Facades\Mail::to($this->email)->send(new \App\Mail\UserWelcome($this));
    }
}
