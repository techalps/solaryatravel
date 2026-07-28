<?php

/*
|--------------------------------------------------------------------------
| Dizionario contenuti da database (IT → EN)
|--------------------------------------------------------------------------
|
| Chiave = testo italiano ESATTO così come sta a DB (titoli, descrizioni,
| itinerari, voci incluso/escluso, etichette delle fasce d'età).
|
| Usato dall'helper tdb() (app/Helpers/translation.php). Se una voce manca,
| tdb() restituisce l'italiano: il sito degrada elegantemente invece di
| mostrare una chiave grezza o del testo vuoto.
|
| Il confronto è normalizzato (trim, spazi collassati, apostrofi tipografici
| ' e ’ equivalenti, &nbsp; → spazio), quindi non serve replicare a mano le
| varianti di punteggiatura inserite dall'editor dell'admin.
|
| Verifica la copertura con: php artisan i18n:missing --locale=en
|
*/

return [

    /* ---------- Voci brevi: incluso/escluso, pasti, luoghi ---------- */

    'Frutta Mista' => 'Mixed fruit',
    'Light Lunch' => 'Light lunch',
    'Aperitivo' => 'Aperitif',
    'Acqua' => 'Water',
    'Caffè' => 'Coffee',
    'Acqua e Caffè illimitato' => 'Unlimited water and coffee',
    'Due Calici di Prosecco o Vino' => 'Two glasses of Prosecco or wine',
    'Pizza Focaccia' => 'Pizza focaccia',
    'Taralli, Olive e Pane Guttiau' => 'Taralli, olives and Guttiau bread',
    'Spuntino (10:30 – 11:30) — Frutta fresca' => 'Snack (10:30 – 11:30) — Fresh fruit',
    'Light Lunch (13:00 – 14:00) — Insalata di farro, gamberetti e zucchine; Fragola con stracciatella, datterini e olio al basilico; Chicken Salad' => 'Light Lunch (13:00 – 14:00) — Spelt salad with shrimp and courgettes; Strawberry with stracciatella, cherry tomatoes and basil oil; Chicken salad',
    'Aperitivo (16:00) — Prosecco, olive, taralli, pane Guttiau' => 'Aperitif (16:00) — Prosecco, olives, taralli, Guttiau bread',
    'Su richiesta' => 'On request',
    'Fascia bambini' => 'Children',
    'Fascia 0-12' => 'Age 0-12',
    'Fascia 13 in poi' => 'Age 13 and up',

    /* ---------- Solarya Daily Escape ---------- */

    'Salpa con noi e lasciati conquistare dalla magia della Sardegna! Un\'esperienza esclusiva tra comfort, natura e libertà, pensata per chi vuole vivere il mare da protagonista e portare a casa ricordi indimenticabili.' => 'Set sail with us and let yourself be won over by the magic of Sardinia! An exclusive experience blending comfort, nature and freedom, designed for those who want to take center stage at sea and bring home unforgettable memories.',

    'La prima sosta meravigliosa che ti aspetta è nelle celebri Piscina di Molara, un angolo di paradiso famoso per i suoi fondali cristallini e i colori incredibili dell\'acqua, perfetti per una nuotata rigenerante o per fare snorkeling tra pesci e natura incontaminata.' => 'The first wonderful stop awaiting you is at the famous Piscina di Molara, a corner of paradise renowned for its crystal-clear seabed and the incredible colours of the water — perfect for a refreshing swim or for snorkelling among fish and unspoiled nature.',

    'Proseguiremo la navigazione verso la suggestiva Spiaggia delle Vacche, piccola e riservata, con sabbia chiara e rocce granitiche scolpite dal vento e rimarrai incantato da Cala Girgolu, una baia dal fascino selvaggio e raffinato, celebre per le sue rocce dalle forme curiose e il mare trasparente che invita a tuffarsi.' => 'We will then continue sailing towards the charming Spiaggia delle Vacche, small and secluded, with light-coloured sand and granite rocks sculpted by the wind, and you\'ll be enchanted by Cala Girgolu, a bay with a wild yet refined charm, famous for its curiously shaped rocks and the clear sea that invites you to dive in.',

    'La seconda sosta continua davanti al promontorio di Capo Coda Cavallo, uno dei punti panoramici più belli della costa nord-orientale, da cui si gode una vista mozzafiato sull\'Area Marina Protetta di Tavolara.' => 'The second stop continues in front of the Capo Coda Cavallo promontory, one of the most beautiful scenic points on the north-eastern coast, offering a breathtaking view over the Tavolara Marine Protected Area.',

    'Infine raggiungeremo per l\'ultima sosta la splendida Cala Brandinchi, conosciuta come la "Piccola Tahiti" per la sua sabbia bianca finissima e il mare dalle sfumature caraibiche, il luogo perfetto per concludere la giornata tra sole, sorrisi e pura meraviglia.' => 'For our final stop, we will reach the stunning Cala Brandinchi, known as "Little Tahiti" for its extremely fine white sand and Caribbean-like sea shades — the perfect place to end the day amid sunshine, smiles and pure wonder.',

    'Piscina di Molara (sosta) – Cala Girgolu & Spiagge delle Vacche (navigazione) – Capo Coda Cavallo (sosta) – Cala Brandinchi (sosta)' => 'Piscina di Molara (stop) – Cala Girgolu & Spiagge delle Vacche (sailing) – Capo Coda Cavallo (stop) – Cala Brandinchi (stop)',

    /* ---------- Solarya Sunset Escape ---------- */

    'Il tramonto più bello della Costa' => 'The most beautiful sunset on the Coast',

    'Vivi la magia del tramonto in Sardegna con un\'esclusiva escursione in catamarano tra la splendida spiaggia La Cinta e l\'elegante cornice di Puntaldia, due dei luoghi più affascinanti della costa di San Teodoro.' => 'Experience the magic of the Sardinian sunset on an exclusive catamaran excursion between the beautiful La Cinta beach and the elegant setting of Puntaldia, two of the most fascinating spots on the San Teodoro coast.',

    'Quando il sole inizia a scendere lentamente sull\'orizzonte, il mare si accende di riflessi dorati e il cielo si tinge di sfumature rosa, arancio e viola, regalando uno spettacolo naturale che lascia senza fiato.' => 'As the sun begins to slowly sink towards the horizon, the sea lights up with golden reflections and the sky is painted with shades of pink, orange and purple, offering a breathtaking natural spectacle.',

    'A bordo del nostro elegante catamarano potrai rilassarti cullato dalle onde, sorseggiare un aperitivo fronte mare e lasciarti avvolgere dall\'atmosfera unica di questo tratto di costa, con vista sull\'imponente profilo dell\'isola di Tavolara che rende il panorama ancora più suggestivo.' => 'Aboard our elegant catamaran you can relax, gently rocked by the waves, sip an aperitif facing the sea and let yourself be enveloped by the unique atmosphere of this stretch of coast, with a view of the imposing silhouette of the island of Tavolara that makes the scenery even more striking.',

    'Navigheremo dolcemente tra la fine della spiaggia La Cinta e le acque cristalline di Puntaldia, in uno scenario esclusivo fatto di natura incontaminata, mare trasparente e tramonti indimenticabili. Avrai la possibilità di tuffarti nelle acque calme illuminate dagli ultimi raggi del sole, o semplicemente goderti il silenzio del mare al calar della sera.' => 'We will sail gently between the far end of La Cinta beach and the crystal-clear waters of Puntaldia, in an exclusive setting of unspoiled nature, transparent sea and unforgettable sunsets. You\'ll have the chance to dive into the calm waters lit by the last rays of the sun, or simply enjoy the silence of the sea as evening falls.',

    'Ogni istante sarà un\'esperienza da vivere intensamente, lontano dalla folla e immerso in una delle atmosfere più romantiche della Sardegna.' => 'Every moment will be an experience to be lived intensely, far from the crowds and immersed in one of the most romantic atmospheres in Sardinia.',

    'Quando il sole scomparirà dietro l\'orizzonte, il catamarano diventerà il luogo perfetto per condividere emozioni speciali: una serata romantica, un brindisi con amici o un ricordo unico della tua vacanza.' => 'Once the sun disappears below the horizon, the catamaran becomes the perfect place to share special moments: a romantic evening, a toast with friends, or a unique memory of your holiday.',

    'Un\'esperienza elegante, rilassante e incredibilmente suggestiva, pensata per chi desidera vivere il mare da una prospettiva esclusiva e lasciarsi conquistare dalla magia del tramonto tra La Cinta e Puntaldia.' => 'An elegant, relaxing and remarkably evocative experience, designed for those who wish to experience the sea from an exclusive perspective and be won over by the magic of the sunset between La Cinta and Puntaldia.',

    /* ---------- Solarya Private Cruise ---------- */

    'Non prenotare un posto ✨ Prenota l\'intero mare 🌊' => 'Don\'t book a seat ✨ Book the whole sea 🌊',

    'Vivi la Sardegna più autentica da una prospettiva privilegiata.' => 'Experience the most authentic Sardinia from a privileged perspective.',

    'Con la nostra escursione privata in esclusiva, il catamarano sarà interamente riservato a te e ai tuoi ospiti, per una giornata all\'insegna del relax, del comfort e della libertà assoluta.' => 'With our exclusive private excursion, the catamaran will be entirely reserved for you and your guests, for a day devoted to relaxation, comfort and absolute freedom.',

    'Nessuna folla, nessun programma rigido: solo il piacere di scegliere i tuoi ritmi, fermarti dove desideri e goderti ogni istante circondato dal mare.' => 'No crowds, no rigid schedule: just the pleasure of setting your own pace, stopping wherever you like and enjoying every moment surrounded by the sea.',

    'Che si tratti di una ricorrenza speciale, di una giornata romantica, di un\'esperienza in famiglia o di un evento privato con amici, il nostro equipaggio si prenderà cura di ogni dettaglio per offrirti un servizio personalizzato e un\'esperienza indimenticabile.' => 'Whether it\'s a special occasion, a romantic day out, a family experience or a private event with friends, our crew will take care of every detail to offer you a personalized service and an unforgettable experience.',

    'Lasciati cullare dal vento, tuffati nelle acque turchesi delle baie più esclusive e scopri il fascino unico di San Teodoro e dell\'Area Marina Protetta di Tavolara a bordo di un elegante catamarano riservato solo a te.' => 'Let yourself be rocked by the wind, dive into the turquoise waters of the most exclusive bays and discover the unique charm of San Teodoro and the Tavolara Marine Protected Area aboard an elegant catamaran reserved just for you.',

    'La tua Sardegna esclusiva inizia qui.' => 'Your exclusive Sardinia starts here.',

];
