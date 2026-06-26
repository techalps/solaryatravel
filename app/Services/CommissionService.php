<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Support\BookingLog;

/**
 * Provvigioni del canale B2B.
 *
 * Decisioni bloccate:
 * - Solarya incassa sempre; all'agenzia riconosce una % (provvigione).
 * - commission_amount = rate% del totale IVA inclusa (= total_amount), identico
 *   al prezzo del cliente in autonomia. L'agenzia non incide sul prezzo.
 * - Snapshot: rate e importo salvati sulla singola prenotazione alla creazione;
 *   i report storici non cambiano se in futuro varia la % dell'agenzia.
 * - Maturazione: sul totale (acconto + saldo) → commission_status = earned.
 * - Storno (annullamento/rimborso/penale): commission_amount = 0,
 *   commission_status = reversed. La penale è ricavo di Solarya, non base
 *   provvigionale. Se la commissione era già pagata, il record NON si cancella:
 *   resta reversed per recuperare/compensare al payout successivo.
 */
class CommissionService
{
    /**
     * Attribuisce una prenotazione a un'agenzia e calcola lo snapshot della
     * commissione sul totale IVA inclusa. Usato dal portale (b2b_portal) e dal
     * referral (b2b_referral).
     */
    public function attributeToAgency(Booking $booking, ?User $agency, string $attributionSource): Booking
    {
        if ($agency === null || ! $agency->isB2b()) {
            return $booking;
        }

        $rate = (float) ($agency->commission_rate ?? 0);
        $amount = round((float) $booking->total_amount * $rate / 100, 2);

        $booking->forceFill([
            'b2b_user_id' => $agency->getKey(),
            'attribution_source' => $attributionSource,
            'commission_rate_snapshot' => $rate,
            'commission_amount' => $amount,
            'commission_status' => 'earned',
        ])->save();

        BookingLog::info('b2b_commission', 'Prenotazione attribuita ad agenzia B2B', $booking, [
            'agency_id' => $agency->getKey(),
            'agency' => $agency->agency_name ?: $agency->name,
            'attribution_source' => $attributionSource,
            'commission_rate' => $rate,
            'commission_amount' => $amount,
        ]);

        return $booking;
    }

    /**
     * Storna la commissione di una prenotazione (annullamento/rimborso/penale).
     * Idempotente: se già reversed non fa nulla. Non cancella il record anche se
     * la commissione era già pagata (resta tracciata per il recupero al payout).
     */
    public function reverse(Booking $booking, string $reason = ''): Booking
    {
        if (! $booking->isB2b() || $booking->commission_status === 'reversed') {
            return $booking;
        }

        $wasPaid = (bool) $booking->commission_paid;
        $previousAmount = (float) $booking->commission_amount;

        $booking->forceFill([
            'commission_amount' => 0,
            'commission_status' => 'reversed',
        ])->save();

        BookingLog::info('b2b_commission_reversed', 'Commissione B2B stornata', $booking, [
            'agency_id' => $booking->b2b_user_id,
            'reason' => $reason,
            'previous_amount' => $previousAmount,
            'was_paid' => $wasPaid,
            'note' => $wasPaid ? 'Era già pagata: da recuperare/compensare al prossimo payout.' : null,
        ]);

        return $booking;
    }
}
