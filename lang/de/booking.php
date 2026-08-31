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
        'terms' => 'Sie müssen die Allgemeinen Geschäftsbedingungen akzeptieren.',
        'adult_first_name' => 'Bitte geben Sie den Vornamen jedes Erwachsenen an.',
        'adult_last_name' => 'Bitte geben Sie den Nachnamen jedes Erwachsenen an.',
        'child_first_name' => 'Bitte geben Sie den Vornamen jedes Kindes an.',
        'child_last_name' => 'Bitte geben Sie den Nachnamen jedes Kindes an.',
        'doc_type_required' => 'Bitte wählen Sie für jeden Gast die Dokumentenart.',
        'doc_type_invalid' => 'Ungültige Dokumentenart.',
        'doc_number_required' => 'Bitte geben Sie die Dokumentennummer jedes Gastes an.',
        'doc_country_required' => 'Bitte geben Sie das Ausstellungsland des Dokuments an.',
        'doc_place_required' => 'Bitte geben Sie den Ausstellungsort des Dokuments an.',
        'doc_province_required' => 'Bitte wählen Sie die ausstellende Provinz.',
    ],

    'errors' => [
        'account_exists' => 'Mit dieser E-Mail-Adresse besteht bereits ein Konto. Melden Sie sich an, bevor Sie fortfahren, oder deaktivieren Sie „Konto erstellen".',
        'no_date' => 'Bitte wählen Sie ein Abfahrtsdatum.',
        'departure_unavailable' => 'Diese Abfahrt ist nicht mehr verfügbar.',
        'bookings_closed' => 'Die Buchungen für diese Abfahrt sind geschlossen (Annahmeschluss um :time am Vortag).',
        'need_adult' => 'Mindestens ein Erwachsener ist erforderlich.',
        'children_dob' => 'Bitte prüfen Sie die Geburtsdaten der Kinder: Jedes muss einer verfügbaren Ermäßigung entsprechen.',
        'invalid_date' => 'Ungültiges Datum.',
        'dob_after_departure' => 'Das Geburtsdatum muss vor dem Abfahrtsdatum liegen.',
    ],

    'discount' => [
        'invalid' => 'Code ungültig oder abgelaufen.',
        'applied' => 'Code erfolgreich angewendet.',
    ],

    'doc_types' => [
        'carta_identita' => 'Personalausweis',
        'passaporto' => 'Reisepass',
        'patente' => 'Führerschein',
    ],

];
