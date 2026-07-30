<?php

/*
 * Messaggi di validazione ed errore del form di prenotazione pubblico
 * (App\Livewire\Public\BookingForm).
 *
 * NB: le chiavi doc_country/doc_place/doc_province restano per compatibilità
 * con eventuali dati storici, ma quei campi non si chiedono più: del documento
 * si raccolgono solo tipo e numero.
 */

return [

    'validation' => [
        'terms' => 'Debes aceptar los términos y condiciones.',
        'adult_first_name' => 'Indica el nombre de cada adulto.',
        'adult_last_name' => 'Indica los apellidos de cada adulto.',
        'child_first_name' => 'Indica el nombre de cada niño.',
        'child_last_name' => 'Indica los apellidos de cada niño.',
        'doc_type_required' => 'Elige el tipo de documento de cada pasajero.',
        'doc_type_invalid' => 'Tipo de documento no válido.',
        'doc_number_required' => 'Indica el número de documento de cada pasajero.',
        'doc_country_required' => 'Indica el país de expedición del documento.',
        'doc_place_required' => 'Indica el lugar de expedición del documento.',
        'doc_province_required' => 'Selecciona la provincia de expedición.',
    ],

    'errors' => [
        'account_exists' => 'Ya existe una cuenta con este correo electrónico. Inicia sesión antes de continuar o desmarca «Crear una cuenta».',
        'no_date' => 'Selecciona una fecha de salida.',
        'departure_unavailable' => 'Esta salida ya no está disponible.',
        'bookings_closed' => 'Las reservas para esta salida están cerradas (el cierre es a las :time del día anterior).',
        'need_adult' => 'Se requiere al menos un adulto.',
        'children_dob' => 'Comprueba las fechas de nacimiento de los niños: cada una debe corresponder a una reducción disponible.',
        'invalid_date' => 'Fecha no válida.',
        'dob_after_departure' => 'La fecha de nacimiento debe ser anterior a la fecha de salida.',
    ],

    'discount' => [
        'invalid' => 'Código no válido o caducado.',
        'applied' => 'Código aplicado correctamente.',
    ],

    'doc_types' => [
        'carta_identita' => 'Documento de identidad',
        'passaporto' => 'Pasaporte',
        'patente' => 'Carné de conducir',
    ],

];
