<?php

return [

    'nav' => [
        'home' => 'Home',
        'tours' => 'Tours',
        'whatsapp' => 'Message us on WhatsApp',
        'login' => 'Log in',
        'book_now' => 'Book Now',
    ],

    'search' => [
        'tour_label' => 'Tour:',
        'all_tours' => 'All tours',
        'departure_date' => 'Departure date:',
        'guests' => 'Guests:',
        'add_guests' => '+ Add guests',
        'adults' => 'Adults',
        'children' => 'Children',
        'ok' => 'Ok',
        'search' => 'Search',
    ],

    'footer' => [
        'tagline' => 'Exclusive catamaran experiences along the coasts of Sardinia. Comfort, elegance and impeccable service for unforgettable moments at sea.',
        'quick_links' => 'Quick Links',
        // "Information" è incontabile in inglese: mai "Informations".
        'information' => 'Information',
        'book_online' => 'Book Online',
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms and Conditions',
        'cookie_policy' => 'Cookie Policy',
        'cookie_prefs' => 'Cookie preferences',
        'contacts' => 'Contacts',
        // Il ", Italy" in coda è voluto: la clientela EN è estera.
        'address' => 'Via Toscanini 9/C, 07026 Olbia (SS), Italy',
        'copyright' => 'Copyright © :year Solarya Travel │ All rights reserved',
        'powered_by' => 'powered by :vendor',
    ],

    'account' => [
        'my_profile' => 'My profile',
        'my_bookings' => 'My bookings',
        'logout' => 'Log out',
    ],

    'a11y' => [
        'close' => 'Close',
        'language' => 'Language',
    ],

    /*
     * Banner e pannello preferenze cookie (GDPR / Garante Privacy).
     */
    'cookie' => [
        'banner_label' => 'Cookie notice',
        'banner_title' => '🍪 We respect your privacy',
        'banner_text' => 'We use technical cookies necessary for the website to work and, with your consent, statistics and marketing cookies (Google Analytics, Meta Pixel). You can accept, reject or customise your choices. More details in our :policy.',
        'policy_link' => 'Cookie Policy',
        'customize' => 'Customise',
        'reject' => 'Reject',
        'accept_all' => 'Accept all',
        'prefs_title' => 'Cookie preferences',
        'necessary_name' => 'Necessary cookies',
        'always_on' => 'Always on',
        'necessary_desc' => 'Essential for navigation, authentication and booking management. They cannot be disabled.',
        'statistics_name' => 'Statistics cookies',
        'statistics_desc' => 'They help us understand how the site is used in aggregate form (Google Analytics 4 with anonymised IP).',
        'marketing_name' => 'Marketing cookies',
        'marketing_desc' => 'Used to measure advertising campaigns and show relevant ads (Meta Pixel, Google Ads).',
        'reject_all' => 'Reject all',
        'save' => 'Save preferences',
    ],

    /*
     * Stringhe esposte al JavaScript del frontend via window.i18n.
     * Vedi layouts/public.blade.php (@json(__('common.js'))).
     */
    'js' => [
        'adult' => 'adult',
        'adults' => 'adults',
        'child' => 'child',
        'children' => 'children',
        'add_guests' => '+ Add guests',
        'select_date' => 'Select a date',
        'date_placeholder' => 'dd/mm/yyyy',
    ],

];
