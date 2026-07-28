<?php

/*
 * Messaggi di validazione ed errore del form di prenotazione pubblico
 * (App\Livewire\Public\BookingForm).
 */

return [

    'validation' => [
        'terms' => 'You must accept the terms and conditions.',
        'tax_code_required' => 'Enter the lead booker\'s tax code.',
        'tax_code_invalid' => 'Invalid tax code.',
        'adult_first_name' => 'Enter the first name of every adult.',
        'adult_last_name' => 'Enter the last name of every adult.',
        'child_first_name' => 'Enter the first name of every child.',
        'child_last_name' => 'Enter the last name of every child.',
        'doc_type_required' => 'Choose the document type for every passenger.',
        'doc_type_invalid' => 'Invalid document type.',
        'doc_number_required' => 'Enter the document number of every passenger.',
        'doc_expiry_required' => 'Enter the document expiry date.',
        'doc_expiry_after' => 'The document must be valid until the date of travel.',
        'doc_country_required' => 'Specify the country that issued the document.',
        'doc_place_required' => 'Specify the place where the document was issued.',
        'doc_province_required' => 'Select the province of issue.',
    ],

    'errors' => [
        'account_exists' => 'An account with this email already exists. Log in before continuing, or untick "Create an account".',
        'no_date' => 'Select a departure date.',
        'departure_unavailable' => 'This departure is no longer available.',
        'bookings_closed' => 'Bookings for this departure are closed (booking closes at :time the day before).',
        'need_adult' => 'At least one adult is required.',
        'children_dob' => 'Check the children\'s dates of birth: each one must match an available reduction.',
        'invalid_date' => 'Invalid date.',
        'dob_after_departure' => 'The date of birth must be earlier than the departure date.',
    ],

    'discount' => [
        'invalid' => 'Invalid or expired code.',
        'applied' => 'Code applied successfully.',
    ],

    'doc_types' => [
        'carta_identita' => 'Identity card',
        'passaporto' => 'Passport',
        'patente' => 'Driving licence',
    ],

];
