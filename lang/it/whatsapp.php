<?php

/*
|--------------------------------------------------------------------------
| Link "click to chat" WhatsApp
|--------------------------------------------------------------------------
|
| Testi precompilati dei link wa.me generati da App\Support\WhatsApp.
|
| - admin_message: lo scrive l'operatore al cliente. Parte sempre in italiano
|   nella struttura, ma è il testo che il CLIENTE leggerà: se serve tradurlo
|   nella lingua della prenotazione, va passato il locale a WhatsApp::adminMessage.
| - customer_message: lo scrive il cliente a Solarya, nella lingua in cui ha
|   prenotato. Cita la prenotazione così lo staff la individua subito.
|
*/

return [
    'admin_message' => 'Ciao :name, ti scriviamo da Solarya Travel riguardo alla tua prenotazione #:booking (:tour del :date).',
    'customer_message' => 'Salve, sono :name. Vi scrivo per la prenotazione #:booking (:tour del :date).',

    'contact_customer' => 'Scrivi su WhatsApp',
    'contact_us' => 'Scrivici su WhatsApp',
    'no_phone' => 'Nessun numero di telefono in prenotazione',
    'invalid_phone' => 'Numero non valido per WhatsApp',
    'not_configured' => 'Numero WhatsApp aziendale non configurato',
    'help_text' => 'Hai bisogno di aiuto? Scrivici su WhatsApp: rispondiamo il prima possibile.',
];
