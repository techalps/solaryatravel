<?php

/*
| Vedi lang/it/emails.php per la struttura e le note.
| I nomi dei :placeholder devono restare identici in ogni lingua.
*/

return [

    'common' => [
        'greeting' => 'Guten Tag :name,',
        'booking' => 'Buchung',
        'tour' => 'Ausflug',
        'date_departure' => 'Datum und Abfahrt',
        'date' => 'Datum',
        'participants' => 'Teilnehmer',
        'passengers' => 'Gäste',
        'total' => 'Gesamt',
        'questions' => 'Bei Fragen antworten Sie einfach auf diese E-Mail.',
        'rights' => 'Alle Rechte vorbehalten',
        'footer_help' => 'Noch Fragen? Antworten Sie auf diese E-Mail oder wenden Sie sich an unser Team.',
    ],

    'payment_link' => [
        'subject' => 'Schließen Sie die Zahlung Ihrer Buchung Nr. :number ab',
        'title' => 'Bestätigen und bezahlen Sie Ihre Buchung',
        'intro' => 'vielen Dank für Ihre Buchung! Um Ihren Ausflug :tour abzuschließen, zahlen Sie bitte sicher online über Stripe.',
        'total_due' => 'Zu zahlender Gesamtbetrag',
        'cta' => 'Mit Karte bezahlen',
        'fallback' => 'Falls die Schaltfläche nicht funktioniert, kopieren Sie diesen Link in Ihren Browser:',
        'deadline' => 'Bitte schließen Sie die Zahlung bis zum :date ab, andernfalls verfällt die Buchung und die Plätze werden wieder freigegeben.',
        'after_payment' => 'Nach erfolgter Zahlung erhalten Sie eine zweite E-Mail mit Ihren Tickets und den QR-Codes, die Sie beim Einstieg vorzeigen (einer pro Gast).',
        'important' => 'Wichtig:',
        'link_expires' => 'Der Zahlungslink verfällt am :date.',
    ],

    'awaiting_transfer' => [
        'subject' => 'Hinweise zur Überweisung · Buchung Nr. :number',
        'title' => 'Schließen Sie Ihre Zahlung per Überweisung ab',
        'intro' => 'wir haben Ihre Buchung Nr. :number für :tour registriert. Zur Bestätigung überweisen Sie bitte den Betrag mit den unten stehenden Bankdaten.',
        'amount' => 'Zu überweisender Betrag',
        'bank_details' => 'Bankverbindung',
        'reference' => 'Verwendungszweck: :number',
        'after' => 'Sobald die Überweisung eingegangen ist, bestätigen wir die Buchung und senden Ihnen die Tickets per E-Mail.',
        'hint' => 'Geben Sie im Verwendungszweck immer die Buchungsnummer an, damit die Prüfung schneller geht.',
    ],

    'balance_reminder' => [
        'subject' => 'Begleichen Sie den Restbetrag Ihrer Buchung Nr. :number',
        'title' => 'Schließen Sie die Zahlung des Restbetrags ab',
        'intro' => 'der Termin Ihres Ausflugs :tour rückt näher. Um die Buchung Nr. :number abzuschließen, erinnern wir Sie an die Zahlung des offenen Betrags.',
        'deposit_paid' => 'Geleistete Anzahlung',
        'balance_due' => 'Offener Restbetrag',
        'deadline' => '⏰ Zu zahlen bis zum :date.',
        'cta' => 'Restbetrag bezahlen',
    ],

    'tickets' => [
        'subject' => 'Ihre Tickets · Buchung Nr. :number',
        'title' => 'Zahlung bestätigt — hier sind Ihre Tickets!',
        'intro' => 'Ihre Zahlung ist erfolgreich eingegangen. Im Anhang finden Sie das PDF mit den Tickets aller Gäste.',
        'instructions' => 'Drucken Sie es aus oder zeigen Sie es beim Einstieg auf Ihrem Smartphone: Jedes Ticket hat einen QR-Code, der zur Erfassung der Anwesenheit gescannt wird.',
        'attachment' => '📎 Anhang: biglietti-:number.pdf',
        'tip_label' => 'Tipp:',
        'tip' => 'Kommen Sie mindestens 15 Minuten vor der Abfahrt an den Steg, mit dem Ticket-PDF (ausgedruckt oder auf dem Smartphone).',
    ],

    'reminder_48h' => [
        'subject' => 'Ausflug in 2 Tagen — Buchung :number',
        'eyebrow' => 'Erinnerung · 48 Stunden',
        'title' => 'Ihr Ausflug rückt näher!',
        'intro_with_time' => 'Ihr Ausflug :tour ist für den :date um :time Uhr geplant.',
        'intro_without_time' => 'Ihr Ausflug :tour ist für den :date geplant.',
        'instructions' => 'Wir erinnern Sie daran, mindestens 15 Minuten vor der Abfahrt am Steg zu sein, mit dem Ticket-PDF (der Bestätigungs-E-Mail beigefügt), ausgedruckt oder auf dem Smartphone. Der QR-Code jedes Tickets wird beim Einstieg gescannt.',
        'closing' => 'Gute Fahrt!',
    ],

    'reminder_24h' => [
        'subject' => 'Morgen geht es los! Erinnerung zur Anmeldung - :number',
        'eyebrow' => 'Abfahrt morgen',
        'title' => '🌊 Ist alles bereit für Ihren Ausflug?',
        'intro' => 'morgen geht es los! Hier eine Übersicht Ihrer Buchung und der erfassten Teilnehmer.',
        'registered' => 'Erfasste Teilnehmer (:count)',
        'lead_booker' => 'Buchender',
        'tax_code' => 'Steuernummer',
    ],

    'cancelled' => [
        'subject' => 'Buchung storniert · Nr. :number',
        'title' => 'Buchung storniert',
        'intro' => 'wir bestätigen Ihnen, dass Ihre Buchung Nr. :number storniert wurde.',
        'cancelled_at' => 'Storniert am',
        'reason' => 'Grund',
        'refund_detail' => 'Einzelheiten zur Erstattung',
        'amount_paid' => 'Gezahlter Betrag',
        'refund' => 'Erstattung (:percentage %)',
        'penalty' => 'Einbehaltene Stornogebühr',
    ],

    'refunded' => [
        'subject' => 'Erstattung ausgeführt · Buchung Nr. :number',
        'title' => 'Erstattung ausgeführt',
        'intro' => 'wir bestätigen Ihnen, dass die Erstattung für die Buchung Nr. :number ausgeführt wurde.',
        'timing' => 'Die Gutschrift auf der für die Zahlung verwendeten Karte kann je nach Bank 5 bis 10 Werktage dauern.',
        'amount' => 'Erstatteter Betrag',
        'penalty' => 'Stornogebühr',
        'booked_date' => 'Gebuchtes Datum',
        'note' => 'Hinweis',
        'closing' => 'Bei Fragen zur Gutschrift oder zu den Fristen antworten Sie einfach auf diese E-Mail.',
    ],

    'welcome' => [
        'subject' => 'Willkommen bei :app',
    ],

];
