<?php

return [

    'nav' => [
        'home' => 'Startseite',
        'tours' => 'Ausflüge',
        'whatsapp' => 'Schreiben Sie uns auf WhatsApp',
        'login' => 'Anmelden',
        'book_now' => 'Jetzt buchen',
    ],

    'search' => [
        'tour_label' => 'Ausflug:',
        'all_tours' => 'Alle Ausflüge',
        'departure_date' => 'Abfahrtsdatum:',
        'guests' => 'Teilnehmer:',
        'add_guests' => '+ Teilnehmer hinzufügen',
        'adults' => 'Erwachsene',
        'children' => 'Kinder',
        'ok' => 'Ok',
        'search' => 'Suchen',
    ],

    'footer' => [
        'tagline' => 'Exklusive Katamaran-Erlebnisse entlang der Küsten Sardiniens. Komfort, Eleganz und tadelloser Service für unvergessliche Momente auf dem Meer.',
        'quick_links' => 'Schnellzugriff',
        'information' => 'Informationen',
        'book_online' => 'Online buchen',
        'privacy' => 'Datenschutzerklärung',
        'terms' => 'Allgemeine Geschäftsbedingungen',
        'cookie_policy' => 'Cookie-Richtlinie',
        'cookie_prefs' => 'Cookie-Einstellungen',
        'contacts' => 'Kontakt',
        // Il ", Italien" in coda è voluto: la clientela DE è estera (come per EN/FR).
        'address' => 'Via Toscanini 9/C, 07026 Olbia (SS), Italien',
        'copyright' => 'Copyright © :year Solarya Travel │ Alle Rechte vorbehalten',
        'powered_by' => 'powered by :vendor',
    ],

    'account' => [
        'my_profile' => 'Mein Profil',
        'my_bookings' => 'Meine Buchungen',
        'logout' => 'Abmelden',
    ],

    'a11y' => [
        'close' => 'Schließen',
        'language' => 'Sprache',
    ],

    /*
     * Banner e pannello preferenze cookie (GDPR / Garante Privacy).
     */
    'cookie' => [
        'banner_label' => 'Cookie-Hinweis',
        'banner_title' => '🍪 Wir respektieren Ihre Privatsphäre',
        'banner_text' => 'Wir verwenden technisch notwendige Cookies für den Betrieb der Website und, mit Ihrer Einwilligung, Statistik- und Marketing-Cookies (Google Analytics, Meta Pixel). Sie können alle akzeptieren, ablehnen oder Ihre Auswahl anpassen. Weitere Einzelheiten in der :policy.',
        'policy_link' => 'Cookie-Richtlinie',
        'customize' => 'Anpassen',
        'reject' => 'Ablehnen',
        'accept_all' => 'Alle akzeptieren',
        'prefs_title' => 'Cookie-Einstellungen',
        'necessary_name' => 'Notwendige Cookies',
        'always_on' => 'Immer aktiv',
        'necessary_desc' => 'Unerlässlich für Navigation, Anmeldung und Buchungsverwaltung. Sie können nicht deaktiviert werden.',
        'statistics_name' => 'Statistik-Cookies',
        'statistics_desc' => 'Sie helfen uns zu verstehen, wie die Website genutzt wird, in aggregierter Form (Google Analytics 4 mit anonymisierter IP).',
        'marketing_name' => 'Marketing-Cookies',
        'marketing_desc' => 'Werden verwendet, um Werbekampagnen zu messen und relevante Anzeigen auszuspielen (Meta Pixel, Google Ads).',
        'reject_all' => 'Alle ablehnen',
        'save' => 'Einstellungen speichern',
    ],

    /*
     * Stringhe esposte al JavaScript del frontend via window.i18n.
     * Vedi layouts/public.blade.php (@json(__('common.js'))).
     */
    'js' => [
        'adult' => 'Erwachsener',
        'adults' => 'Erwachsene',
        'child' => 'Kind',
        'children' => 'Kinder',
        'add_guests' => '+ Teilnehmer hinzufügen',
        'select_date' => 'Datum auswählen',
        'date_placeholder' => 'TT.MM.JJJJ',
    ],

];
