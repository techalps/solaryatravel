<?php

/*
 * Messaggi di validazione ed errore del form di prenotazione pubblico
 * (App\Livewire\Public\BookingForm).
 */

return [

    'validation' => [
        'terms' => 'Devi accettare i termini e condizioni.',
        'tax_code_required' => 'Inserisci il codice fiscale dell\'intestatario.',
        'tax_code_invalid' => 'Codice fiscale non valido.',
        'adult_first_name' => 'Inserisci il nome di ogni adulto.',
        'adult_last_name' => 'Inserisci il cognome di ogni adulto.',
        'child_first_name' => 'Inserisci il nome di ogni bambino.',
        'child_last_name' => 'Inserisci il cognome di ogni bambino.',
        'doc_type_required' => 'Scegli il tipo di documento per ogni passeggero.',
        'doc_type_invalid' => 'Tipo di documento non valido.',
        'doc_number_required' => 'Inserisci il numero del documento di ogni passeggero.',
        'doc_expiry_required' => 'Inserisci la data di scadenza del documento.',
        'doc_expiry_after' => 'Il documento deve essere valido fino alla data del viaggio.',
        'doc_country_required' => 'Indica lo Stato di emissione del documento.',
        'doc_place_required' => 'Indica il luogo di emissione del documento.',
        'doc_province_required' => 'Seleziona la provincia di emissione.',
    ],

    'errors' => [
        'account_exists' => 'Esiste già un account con questa email. Accedi prima di proseguire, oppure deseleziona "Crea un account".',
        'no_date' => 'Seleziona una data di partenza.',
        'departure_unavailable' => 'Questa partenza non è più disponibile.',
        'bookings_closed' => 'Le prenotazioni per questa partenza sono chiuse (si prenota entro le :time del giorno prima).',
        'need_adult' => 'Serve almeno un adulto.',
        'children_dob' => 'Controlla le date di nascita dei bambini: ognuna deve corrispondere a una riduzione disponibile.',
        'invalid_date' => 'Data non valida.',
        'dob_after_departure' => 'La data di nascita deve essere precedente alla data di partenza.',
    ],

    'discount' => [
        'invalid' => 'Codice non valido o scaduto.',
        'applied' => 'Codice applicato correttamente.',
    ],

    'doc_types' => [
        'carta_identita' => 'Carta d\'identità',
        'passaporto' => 'Passaporto',
        'patente' => 'Patente di guida',
    ],

];
