<?php

/*
|--------------------------------------------------------------------------
| Dizionario contenuti da database (IT → DE)
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
| Verifica la copertura con: php artisan i18n:missing --locale=de
|
*/

return [

    /* ---------- Voci brevi: incluso/escluso, pasti, luoghi ---------- */

    'Frutta Mista' => 'Gemischtes Obst',
    'Frutta mista' => 'Gemischtes Obst',
    'Light Lunch' => 'Leichtes Mittagessen',
    'Light lunch' => 'Leichtes Mittagessen',
    'Aperitivo' => 'Aperitif',
    'Acqua' => 'Wasser',
    'Caffè' => 'Kaffee',
    'Acqua e Caffè illimitato' => 'Wasser und Kaffee unbegrenzt',
    'Due Calici di Prosecco o Vino' => 'Zwei Gläser Prosecco oder Wein',
    'Pizza Focaccia' => 'Pizza-Focaccia',
    'Pinsa' => 'Pinsa',
    'Panini' => 'Sandwiches',
    'Beverage' => 'Getränke',
    'Taralli, Olive e Pane Guttiau' => 'Taralli, Oliven und Guttiau-Brot',
    'Spuntino (10:30 – 11:30) — Frutta fresca' => 'Snack (10:30 – 11:30) — Frisches Obst',
    'Light Lunch (13:00 – 14:00) — Insalata di farro, gamberetti e zucchine; Fragola con stracciatella, datterini e olio al basilico; Chicken Salad' => 'Leichtes Mittagessen (13:00 – 14:00) — Dinkelsalat mit Garnelen und Zucchini; Erdbeere mit Stracciatella, Datteltomaten und Basilikumöl; Chicken Salad',
    'Aperitivo (16:00) — Prosecco, olive, taralli, pane Guttiau' => 'Aperitif (16:00) — Prosecco, Oliven, Taralli, Guttiau-Brot',
    'Su richiesta' => 'Auf Anfrage',
    'Porto di partenza' => 'Abfahrtshafen',
    'Fascia bambini' => 'Kinder',
    'Fascia 0-12' => 'Von 0 bis 12 Jahren',
    'Fascia 13 in poi' => 'Ab 13 Jahren',
    '0 - 7' => '0 - 7',
    '8 - 14' => '8 - 14',
    '15 in poi' => '15 und älter',
    'Crociera giornaliere tra acque cristalline' => 'Tagesfahrt durch kristallklares Wasser',
    'Crociera fullday tra acque cristalline' => 'Ganztagesfahrt durch kristallklares Wasser',

    /* ---------- Solarya Daily Escape ---------- */

    'Salpa con noi e lasciati conquistare dalla magia della Sardegna! Un\'esperienza esclusiva tra comfort, natura e libertà, pensata per chi vuole vivere il mare da protagonista e portare a casa ricordi indimenticabili.' => 'Stechen Sie mit uns in See und lassen Sie sich vom Zauber Sardiniens erobern! Ein exklusives Erlebnis aus Komfort, Natur und Freiheit, gedacht für alle, die das Meer hautnah erleben und unvergessliche Erinnerungen mit nach Hause nehmen möchten.',

    'La prima sosta meravigliosa che ti aspetta è nelle celebri Piscina di Molara, un angolo di paradiso famoso per i suoi fondali cristallini e i colori incredibili dell\'acqua, perfetti per una nuotata rigenerante o per fare snorkeling tra pesci e natura incontaminata.' => 'Der erste wunderbare Halt erwartet Sie in den berühmten Piscine di Molara, einem Stück Paradies, bekannt für seinen kristallklaren Grund und die unglaublichen Farben des Wassers — perfekt für ein erfrischendes Bad oder zum Schnorcheln zwischen Fischen und unberührter Natur.',

    'Proseguiremo la navigazione verso la suggestiva Spiaggia delle Vacche, piccola e riservata, con sabbia chiara e rocce granitiche scolpite dal vento e rimarrai incantato da Cala Girgolu, una baia dal fascino selvaggio e raffinato, celebre per le sue rocce dalle forme curiose e il mare trasparente che invita a tuffarsi.' => 'Wir setzen die Fahrt fort zur stimmungsvollen Spiaggia delle Vacche, klein und ruhig, mit hellem Sand und vom Wind geformten Granitfelsen. Verzaubern wird Sie auch Cala Girgolu, eine Bucht von wildem und zugleich edlem Reiz, berühmt für ihre eigenwillig geformten Felsen und das klare Meer, das zum Sprung ins Wasser einlädt.',

    'La seconda sosta continua davanti al promontorio di Capo Coda Cavallo, uno dei punti panoramici più belli della costa nord-orientale, da cui si gode una vista mozzafiato sull\'Area Marina Protetta di Tavolara.' => 'Der zweite Halt findet vor dem Kap Capo Coda Cavallo statt, einem der schönsten Aussichtspunkte der Nordostküste, von dem aus sich ein atemberaubender Blick auf das Meeresschutzgebiet von Tavolara bietet.',

    'Infine raggiungeremo per l\'ultima sosta la splendida Cala Brandinchi, conosciuta come la "Piccola Tahiti" per la sua sabbia bianca finissima e il mare dalle sfumature caraibiche, il luogo perfetto per concludere la giornata tra sole, sorrisi e pura meraviglia.' => 'Zum letzten Halt erreichen wir schließlich die herrliche Cala Brandinchi, bekannt als „Klein-Tahiti" wegen ihres feinen weißen Sandes und des Meeres in karibischen Farbtönen — der perfekte Ort, um den Tag zwischen Sonne, Lachen und purem Staunen ausklingen zu lassen.',

    'Piscina di Molara (sosta) – Cala Girgolu & Spiagge delle Vacche (navigazione) – Capo Coda Cavallo (sosta) – Cala Brandinchi (sosta)' => 'Piscine di Molara (Halt) – Cala Girgolu und Spiagge delle Vacche (Fahrt) – Capo Coda Cavallo (Halt) – Cala Brandinchi (Halt)',

    /* ---------- Solarya Sunset Escape ---------- */

    'Il tramonto più bello della Costa' => 'Der schönste Sonnenuntergang der Küste',

    'Vivi la magia del tramonto in Sardegna con un\'esclusiva escursione in catamarano tra la splendida spiaggia La Cinta e l\'elegante cornice di Puntaldia, due dei luoghi più affascinanti della costa di San Teodoro.' => 'Erleben Sie den Zauber des Sonnenuntergangs auf Sardinien bei einem exklusiven Katamaran-Ausflug zwischen dem herrlichen Strand La Cinta und der eleganten Kulisse von Puntaldia, zwei der reizvollsten Orte an der Küste von San Teodoro.',

    'Quando il sole inizia a scendere lentamente sull\'orizzonte, il mare si accende di riflessi dorati e il cielo si tinge di sfumature rosa, arancio e viola, regalando uno spettacolo naturale che lascia senza fiato.' => 'Wenn die Sonne langsam zum Horizont sinkt, leuchtet das Meer in goldenen Reflexen und der Himmel färbt sich in Rosa-, Orange- und Violetttönen — ein Naturschauspiel, das den Atem raubt.',

    'A bordo del nostro elegante catamarano potrai rilassarti cullato dalle onde, sorseggiare un aperitivo fronte mare e lasciarti avvolgere dall\'atmosfera unica di questo tratto di costa, con vista sull\'imponente profilo dell\'isola di Tavolara che rende il panorama ancora più suggestivo.' => 'An Bord unseres eleganten Katamarans können Sie sich von den Wellen wiegen lassen, einen Aperitif mit Blick aufs Meer genießen und die einzigartige Atmosphäre dieses Küstenabschnitts auf sich wirken lassen — mit Blick auf die imposante Silhouette der Insel Tavolara, die das Panorama noch eindrucksvoller macht.',

    'Navigheremo dolcemente tra la fine della spiaggia La Cinta e le acque cristalline di Puntaldia, in uno scenario esclusivo fatto di natura incontaminata, mare trasparente e tramonti indimenticabili. Avrai la possibilità di tuffarti nelle acque calme illuminate dagli ultimi raggi del sole, o semplicemente goderti il silenzio del mare al calar della sera.' => 'Wir gleiten sanft zwischen dem Ende des Strandes La Cinta und den kristallklaren Gewässern von Puntaldia dahin, in einer exklusiven Kulisse aus unberührter Natur, klarem Meer und unvergesslichen Sonnenuntergängen. Sie können in das ruhige, von den letzten Sonnenstrahlen beleuchtete Wasser eintauchen oder einfach die Stille des Meeres bei Einbruch des Abends genießen.',

    'Ogni istante sarà un\'esperienza da vivere intensamente, lontano dalla folla e immerso in una delle atmosfere più romantiche della Sardegna.' => 'Jeder Augenblick wird zu einem intensiven Erlebnis, fern der Menge und eingetaucht in eine der romantischsten Stimmungen Sardiniens.',

    'Quando il sole scomparirà dietro l\'orizzonte, il catamarano diventerà il luogo perfetto per condividere emozioni speciali: una serata romantica, un brindisi con amici o un ricordo unico della tua vacanza.' => 'Wenn die Sonne hinter dem Horizont verschwindet, wird der Katamaran zum perfekten Ort für besondere Momente: ein romantischer Abend, ein Anstoßen mit Freunden oder eine einzigartige Erinnerung an Ihren Urlaub.',

    'Un\'esperienza elegante, rilassante e incredibilmente suggestiva, pensata per chi desidera vivere il mare da una prospettiva esclusiva e lasciarsi conquistare dalla magia del tramonto tra La Cinta e Puntaldia.' => 'Ein elegantes, entspannendes und unglaublich stimmungsvolles Erlebnis, gedacht für alle, die das Meer aus einer exklusiven Perspektive erleben und sich vom Zauber des Sonnenuntergangs zwischen La Cinta und Puntaldia verzaubern lassen möchten.',

    /* ---------- Solarya Private Cruise ---------- */

    'Non prenotare un posto ✨ Prenota l\'intero mare 🌊' => 'Buchen Sie keinen Platz ✨ Buchen Sie das ganze Meer 🌊',

    'Vivi la Sardegna più autentica da una prospettiva privilegiata.' => 'Erleben Sie das authentischste Sardinien aus einer privilegierten Perspektive.',

    'Con la nostra escursione privata in esclusiva, il catamarano sarà interamente riservato a te e ai tuoi ospiti, per una giornata all\'insegna del relax, del comfort e della libertà assoluta.' => 'Bei unserem exklusiven Privatausflug steht der Katamaran ganz Ihnen und Ihren Gästen zur Verfügung — für einen Tag im Zeichen von Entspannung, Komfort und absoluter Freiheit.',

    'Nessuna folla, nessun programma rigido: solo il piacere di scegliere i tuoi ritmi, fermarti dove desideri e goderti ogni istante circondato dal mare.' => 'Keine Menschenmengen, kein starres Programm: nur das Vergnügen, Ihr eigenes Tempo zu bestimmen, dort zu halten, wo Sie möchten, und jeden Augenblick umgeben vom Meer zu genießen.',

    'Che si tratti di una ricorrenza speciale, di una giornata romantica, di un\'esperienza in famiglia o di un evento privato con amici, il nostro equipaggio si prenderà cura di ogni dettaglio per offrirti un servizio personalizzato e un\'esperienza indimenticabile.' => 'Ob ein besonderer Anlass, ein romantischer Tag, ein Familienerlebnis oder eine private Feier mit Freunden: Unsere Crew kümmert sich um jedes Detail, um Ihnen einen persönlichen Service und ein unvergessliches Erlebnis zu bieten.',

    'Lasciati cullare dal vento, tuffati nelle acque turchesi delle baie più esclusive e scopri il fascino unico di San Teodoro e dell\'Area Marina Protetta di Tavolara a bordo di un elegante catamarano riservato solo a te.' => 'Lassen Sie sich vom Wind wiegen, tauchen Sie in das türkisfarbene Wasser der exklusivsten Buchten ein und entdecken Sie den einzigartigen Reiz von San Teodoro und des Meeresschutzgebiets von Tavolara an Bord eines eleganten Katamarans, der nur Ihnen vorbehalten ist.',

    'La tua Sardegna esclusiva inizia qui.' => 'Ihr exklusives Sardinien beginnt hier.',

];
