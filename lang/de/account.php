<?php

/*
| Vedi lang/it/account.php per la struttura e le note.
| I nomi dei :placeholder devono restare identici in ogni lingua.
*/

return [

    'common' => [
        'booking' => 'Buchung',
        'home' => 'Startseite',
        'your_tour' => 'Ihr Ausflug',
        'summary' => 'Übersicht',
        'order_summary' => 'Bestellübersicht',
        'amounts_summary' => 'Zahlungsübersicht',
        'subtotal' => 'Zwischensumme',
        'extras' => 'Extras',
        'discount' => 'Rabatt',
        'vat' => 'MwSt.',
        'vat_included' => 'inkl. MwSt.',
        'total' => 'Gesamt',
        'total_colon' => 'Gesamt:',
        'status' => 'Status',
        'duration' => 'Dauer',
        'date' => 'Datum',
        'method' => 'Zahlungsart',
        'payment' => 'Zahlung',
        'lead_booker' => 'Buchender',
        'name' => 'Name',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'special_requests' => 'Besondere Wünsche',
        'participants' => 'Teilnehmer',
        'person' => ':count Person|:count Personen',
        'participant' => ':count Teilnehmer|:count Teilnehmer',
        'seat' => ':count Platz|:count Plätze',
        'hours' => ':count Stunden',
        'hours_short' => ':count Std.',
        'adult' => 'Erwachsener',
        'participants_count' => 'Teilnehmer',
        'deadline' => 'Frist',
        'actions' => 'Aktionen',
        'cancelled_item' => 'Storniert',
        'boarded' => 'Eingecheckt',
    ],

    'status_labels' => [
        'pending' => 'Zahlung ausstehend',
        'deposit_paid' => 'Anzahlung geleistet',
        'awaiting_transfer' => 'Überweisung ausstehend',
        'confirmed' => 'Bestätigt',
        'checked_in' => 'Eingecheckt',
        'completed' => 'Abgeschlossen',
        'cancelled' => 'Storniert',
        'refunded' => 'Erstattet',
        'no_show' => 'Nicht erschienen',
    ],

    'pay' => [
        'title' => 'Bestätigen und bezahlen',
        'awaiting' => 'Zahlung ausstehend',
        'lead_booker_title' => 'Inhaber der Buchung',
        'extras_included' => 'Enthaltene Extras:',
        'seats_held_for' => 'Plätze reserviert für',
        'secure_payment' => 'Sichere Zahlung',
        'stripe_note' => 'Zahlungsabwicklung über Stripe — Ihre Daten laufen nicht über unseren Server.',
        'instant_confirm' => 'Sofortige Bestätigung und Beleg per E-Mail.',
        'tickets_after' => 'Tickets mit QR-Code direkt nach der Zahlung.',
    ],

    'success' => [
        'title' => 'Buchung bestätigt!',
        'intro' => ':name, wir haben Ihre Zahlung erhalten.',
        'deposit_title' => 'Anzahlung erhalten',
        'deposit_intro' => 'Vielen Dank! Wir haben Ihre Anzahlung verbucht. Ihr Platz ist bestätigt.',
        'deposit_tickets_later' => 'Sie erhalten die Tickets per E-Mail, sobald der Restbetrag bezahlt ist.',
        'by_date' => 'bis zum :date',
        'pay_balance' => 'Restbetrag bezahlen',
        'sent_to' => 'Wir haben die Tickets und den Beleg gesendet an',
        'all_tickets' => 'Tickets aller Teilnehmer',
        'total_paid' => 'Gezahlter Gesamtbetrag',
        'back_home' => 'Zurück zur Startseite',
        'explore_tours' => 'Weitere Ausflüge entdecken',
    ],

    'cancel' => [
        'title' => 'Zahlung abgebrochen',
        'intro' => 'Ihre Buchung ist weiterhin offen — Sie können es jederzeit erneut versuchen.',
        'no_charge' => 'Es wurde kein Betrag abgebucht. Die Buchung bleibt bis zum Ablauf gültig.',
        'total_due' => 'Zu zahlender Gesamtbetrag:',
    ],

    'balance' => [
        'title' => 'Zahlung des Restbetrags',
        'booking_total' => 'Buchungssumme',
        'deposit_paid' => 'Geleistete Anzahlung',
        'balance' => 'Restbetrag',
        'balance_due' => 'Offener Restbetrag',
        'secure_stripe' => 'Sichere Zahlung über Stripe',
        'complete_payment' => 'Zahlung abschließen',
    ],

    'transfer' => [
        'registered' => 'Buchung registriert',
        'title' => 'Schließen Sie die Zahlung per Sofortüberweisung ab',
        'intro' => 'Die Buchung :number wartet auf Zahlung, die Plätze sind reserviert. Sobald die Überweisung eingegangen ist, bestätigen wir sie und Sie erhalten die Tickets per E-Mail.',
        'amount' => 'Zu überweisender Betrag',
        'deposit_then_balance' => 'Anzahlung · Restbetrag später',
        'bank_details' => 'Bankverbindung',
        'reference_hint' => 'Geben Sie die Buchungsnummer als :reference an:',
        'reference_word' => 'Verwendungszweck',
        'go_to_booking' => 'Zu meiner Buchung',
        'transfer' => 'Überweisung',
    ],

    'my_bookings' => [
        'title' => 'Meine Buchungen',
        'subtitle' => 'Verlauf, nächste Abfahrten und Tickets Ihrer Ausflüge.',
        'empty_title' => 'Noch keine Buchungen',
        'empty_text' => 'Entdecken Sie unsere Katamaran-Ausflüge entlang der Küste. Ihr nächstes Erlebnis beginnt hier.',
        'today' => 'Heute',
        'upcoming' => 'Demnächst',
        'user_fallback' => 'Gast',
    ],

    'detail' => [
        'booked_extras' => 'Gebuchte Extras',
        'passenger_todo' => '— Daten noch auszufüllen —',
        'extra_fallback' => 'Extra',
        'tour_fallback' => 'Ausflug',
    ],

    'tickets' => [
        'title' => 'Ihre Tickets',
        'one_per_passenger' => 'Jeder Gast hat sein eigenes Ticket.',
        'passenger' => 'Gast',
        'adult' => 'Erwachsener',
        'seat' => 'Platz',
        'catamaran' => 'Katamaran',
        'departure' => 'Abfahrt',
        'boarding_point' => 'Einstiegsort',
        'boarded' => 'EINGECHECKT',
        'tour' => 'Ausflug',
    ],

];
