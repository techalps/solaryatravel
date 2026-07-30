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
        'terms' => 'Vous devez accepter les conditions générales.',
        'adult_first_name' => 'Indiquez le prénom de chaque adulte.',
        'adult_last_name' => 'Indiquez le nom de chaque adulte.',
        'child_first_name' => 'Indiquez le prénom de chaque enfant.',
        'child_last_name' => 'Indiquez le nom de chaque enfant.',
        'doc_type_required' => 'Choisissez le type de document pour chaque passager.',
        'doc_type_invalid' => 'Type de document non valide.',
        'doc_number_required' => 'Indiquez le numéro de document de chaque passager.',
        'doc_country_required' => 'Indiquez le pays de délivrance du document.',
        'doc_place_required' => 'Indiquez le lieu de délivrance du document.',
        'doc_province_required' => 'Sélectionnez la province de délivrance.',
    ],

    'errors' => [
        'account_exists' => 'Un compte existe déjà avec cette adresse e-mail. Connectez-vous avant de continuer ou décochez « Créer un compte ».',
        'no_date' => 'Sélectionnez une date de départ.',
        'departure_unavailable' => 'Ce départ n\'est plus disponible.',
        'bookings_closed' => 'Les réservations pour ce départ sont closes (clôture à :time la veille).',
        'need_adult' => 'Au moins un adulte est requis.',
        'children_dob' => 'Vérifiez les dates de naissance des enfants : chacune doit correspondre à une réduction disponible.',
        'invalid_date' => 'Date non valide.',
        'dob_after_departure' => 'La date de naissance doit être antérieure à la date de départ.',
    ],

    'discount' => [
        'invalid' => 'Code non valide ou expiré.',
        'applied' => 'Code appliqué avec succès.',
    ],

    'doc_types' => [
        'carta_identita' => 'Carte d\'identité',
        'passaporto' => 'Passeport',
        'patente' => 'Permis de conduire',
    ],

];
