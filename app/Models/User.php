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
        'commission_rate',
        'can_book_complimentary',
        'agency_name',
        'referral_token',
        'widget_allowed_domains',
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
            'commission_rate' => 'decimal:2',
            'can_book_complimentary' => 'boolean',
            'widget_allowed_domains' => 'array',
        ];
    }

    /**
     * L'agenzia è autorizzata a registrare prenotazioni a 0€ (posti omaggio)?
     *
     * Concessione per singola agenzia, non un diritto del ruolo b2b: il flag si
     * abilita dalle impostazioni di quell'agenzia. Vale solo per il ruolo b2b —
     * l'admin ha già i posti omaggio per conto suo.
     */
    public function canBookComplimentary(): bool
    {
        return $this->role === 'b2b' && (bool) $this->can_book_complimentary;
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
     * Include system_admin (tutti i poteri admin più la sezione Sistema) e
     * skipper, che però vede SOLO la sezione Imbarco: il gating fine è nel
     * middleware 'skipper_area' (vedi SkipperAreaMiddleware).
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin', 'system_admin', 'skipper']);
    }

    /**
     * Ruolo operativo di bordo: scansiona i QR dei biglietti all'imbarco e
     * nient'altro. Non vede prenotazioni, incassi, report o impostazioni.
     */
    public function isSkipper(): bool
    {
        return $this->role === 'skipper';
    }

    /**
     * Nome della rotta su cui atterrare dopo il login (o la verifica email).
     *
     * Lo skipper non può vedere la dashboard: va portato direttamente alla sua
     * unica sezione, altrimenti verrebbe rimbalzato dal middleware.
     */
    public function homeRouteName(): string
    {
        if ($this->isSkipper()) {
            return 'admin.boarding.index';
        }

        return $this->isAdmin() ? 'admin.dashboard' : 'bookings.my';
    }

    /**
     * Pieno accesso al gestionale: tutti i ruoli admin TRANNE lo skipper.
     * Da usare dove serve distinguere "è nell'area admin" da "può gestire".
     */
    public function hasFullAdminAccess(): bool
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
     * Agenzia rivenditrice (canale B2B). Accede SOLO all'area b2b, non al sito
     * cliente né all'admin, e non paga (Solarya incassa, riconosce una provvigione).
     */
    public function isB2b(): bool
    {
        return $this->role === 'b2b';
    }

    /**
     * Get the user's bookings (come cliente intestatario).
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Prenotazioni attribuite a questa agenzia (portal o referral).
     */
    public function b2bBookings()
    {
        return $this->hasMany(Booking::class, 'b2b_user_id');
    }

    /**
     * Genera (se mancante) e restituisce il token di referral per i link/QR.
     * Token opaco e univoco: usato in ?ref=... per attribuire la prenotazione.
     */
    public function ensureReferralToken(): string
    {
        if (empty($this->referral_token)) {
            do {
                $token = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(16));
            } while (static::where('referral_token', $token)->exists());

            $this->forceFill(['referral_token' => $token])->save();
        }

        return $this->referral_token;
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
