<?php

return [

    'nav' => [
        'home' => 'Inicio',
        'tours' => 'Excursiones',
        'whatsapp' => 'Escríbenos por WhatsApp',
        'login' => 'Iniciar sesión',
        'book_now' => 'Reservar ahora',
    ],

    'search' => [
        'tour_label' => 'Excursión:',
        'all_tours' => 'Todas las excursiones',
        'departure_date' => 'Fecha de salida:',
        'guests' => 'Huéspedes:',
        'add_guests' => '+ Añadir huéspedes',
        'adults' => 'Adultos',
        'children' => 'Niños',
        'ok' => 'Ok',
        'search' => 'Buscar',
    ],

    'footer' => [
        'tagline' => 'Experiencias exclusivas en catamarán por las costas de Cerdeña. Confort, elegancia y un servicio impecable para momentos inolvidables en el mar.',
        'quick_links' => 'Enlaces rápidos',
        'information' => 'Información',
        'book_online' => 'Reservar online',
        'privacy' => 'Política de privacidad',
        'terms' => 'Términos y condiciones',
        'cookie_policy' => 'Política de cookies',
        'cookie_prefs' => 'Preferencias de cookies',
        'contacts' => 'Contacto',
        // Il ", Italia" in coda è voluto: la clientela ES è estera (come per EN).
        'address' => 'Via Toscanini 9/C, 07026 Olbia (SS), Italia',
        'copyright' => 'Copyright © :year Solarya Travel │ Todos los derechos reservados',
        'powered_by' => 'powered by :vendor',
    ],

    'account' => [
        'my_profile' => 'Mi perfil',
        'my_bookings' => 'Mis reservas',
        'logout' => 'Salir',
    ],

    'a11y' => [
        'close' => 'Cerrar',
        'language' => 'Idioma',
    ],

    /*
     * Banner e pannello preferenze cookie (GDPR / Garante Privacy).
     */
    'cookie' => [
        'banner_label' => 'Aviso de cookies',
        'banner_title' => '🍪 Respetamos tu privacidad',
        'banner_text' => 'Utilizamos cookies técnicas necesarias para el funcionamiento del sitio y, con tu consentimiento, cookies de estadística y marketing (Google Analytics, Meta Pixel). Puedes aceptar, rechazar o personalizar tus preferencias. Más detalles en la :policy.',
        'policy_link' => 'Política de cookies',
        'customize' => 'Personalizar',
        'reject' => 'Rechazar',
        'accept_all' => 'Aceptar todo',
        'prefs_title' => 'Preferencias de cookies',
        'necessary_name' => 'Cookies necesarias',
        'always_on' => 'Siempre activas',
        'necessary_desc' => 'Imprescindibles para la navegación, la autenticación y la gestión de las reservas. No se pueden desactivar.',
        'statistics_name' => 'Cookies estadísticas',
        'statistics_desc' => 'Nos ayudan a entender cómo se usa el sitio de forma agregada (Google Analytics 4 con IP anonimizada).',
        'marketing_name' => 'Cookies de marketing',
        'marketing_desc' => 'Se utilizan para medir las campañas publicitarias y mostrar anuncios relevantes (Meta Pixel, Google Ads).',
        'reject_all' => 'Rechazar todo',
        'save' => 'Guardar preferencias',
    ],

    /*
     * Stringhe esposte al JavaScript del frontend via window.i18n.
     * Vedi layouts/public.blade.php (@json(__('common.js'))).
     */
    'js' => [
        'adult' => 'adulto',
        'adults' => 'adultos',
        'child' => 'niño',
        'children' => 'niños',
        'add_guests' => '+ Añadir huéspedes',
        'select_date' => 'Selecciona una fecha',
        'date_placeholder' => 'dd/mm/aaaa',
    ],

];
