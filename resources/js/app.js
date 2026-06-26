import './bootstrap.js';

// NB: Alpine NON va avviato qui. Livewire 3 include e inizializza il proprio
// Alpine; un secondo Alpine.start() causa "Alpine has already been initialized"
// e blocca tutto il JS di Livewire sulle pagine che montano componenti (es. il
// form di prenotazione del portale B2B). Nel progetto non si usano direttive
// Alpine standalone, quindi rimuoverlo è sicuro.
