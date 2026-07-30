<?php

return [

    'nav' => [
        'home' => 'Accueil',
        'tours' => 'Excursions',
        'whatsapp' => 'Écrivez-nous sur WhatsApp',
        'login' => 'Se connecter',
        'book_now' => 'Réserver',
    ],

    'search' => [
        'tour_label' => 'Excursion :',
        'all_tours' => 'Toutes les excursions',
        'departure_date' => 'Date de départ :',
        'guests' => 'Participants :',
        'add_guests' => '+ Ajouter des participants',
        'adults' => 'Adultes',
        'children' => 'Enfants',
        'ok' => 'Ok',
        'search' => 'Rechercher',
    ],

    'footer' => [
        'tagline' => 'Des expériences exclusives en catamaran le long des côtes de la Sardaigne. Confort, élégance et service impeccable pour des moments inoubliables en mer.',
        'quick_links' => 'Liens rapides',
        'information' => 'Informations',
        'book_online' => 'Réserver en ligne',
        'privacy' => 'Politique de confidentialité',
        'terms' => 'Conditions générales',
        'cookie_policy' => 'Politique de cookies',
        'cookie_prefs' => 'Préférences cookies',
        'contacts' => 'Contact',
        // Il ", Italie" in coda è voluto: la clientela FR è estera (come per EN).
        'address' => 'Via Toscanini 9/C, 07026 Olbia (SS), Italie',
        'copyright' => 'Copyright © :year Solarya Travel │ Tous droits réservés',
        'powered_by' => 'powered by :vendor',
    ],

    'account' => [
        'my_profile' => 'Mon profil',
        'my_bookings' => 'Mes réservations',
        'logout' => 'Déconnexion',
    ],

    'a11y' => [
        'close' => 'Fermer',
        'language' => 'Langue',
    ],

    /*
     * Banner e pannello preferenze cookie (GDPR / Garante Privacy).
     */
    'cookie' => [
        'banner_label' => 'Avis cookies',
        'banner_title' => '🍪 Nous respectons votre vie privée',
        'banner_text' => 'Nous utilisons des cookies techniques nécessaires au fonctionnement du site et, avec votre consentement, des cookies de statistiques et de marketing (Google Analytics, Meta Pixel). Vous pouvez accepter, refuser ou personnaliser vos choix. Plus de détails dans la :policy.',
        'policy_link' => 'Politique de cookies',
        'customize' => 'Personnaliser',
        'reject' => 'Refuser',
        'accept_all' => 'Tout accepter',
        'prefs_title' => 'Préférences cookies',
        'necessary_name' => 'Cookies nécessaires',
        'always_on' => 'Toujours actifs',
        'necessary_desc' => 'Indispensables à la navigation, à l\'authentification et à la gestion des réservations. Ils ne peuvent pas être désactivés.',
        'statistics_name' => 'Cookies statistiques',
        'statistics_desc' => 'Ils nous aident à comprendre comment le site est utilisé, sous forme agrégée (Google Analytics 4 avec IP anonymisée).',
        'marketing_name' => 'Cookies marketing',
        'marketing_desc' => 'Utilisés pour mesurer les campagnes publicitaires et afficher des annonces pertinentes (Meta Pixel, Google Ads).',
        'reject_all' => 'Tout refuser',
        'save' => 'Enregistrer les préférences',
    ],

    /*
     * Stringhe esposte al JavaScript del frontend via window.i18n.
     * Vedi layouts/public.blade.php (@json(__('common.js'))).
     */
    'js' => [
        'adult' => 'adulte',
        'adults' => 'adultes',
        'child' => 'enfant',
        'children' => 'enfants',
        'add_guests' => '+ Ajouter des participants',
        'select_date' => 'Sélectionnez une date',
        'date_placeholder' => 'jj/mm/aaaa',
    ],

];
