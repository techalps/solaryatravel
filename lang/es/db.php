<?php

/*
|--------------------------------------------------------------------------
| Dizionario contenuti da database (IT → ES)
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
| Verifica la copertura con: php artisan i18n:missing --locale=es
|
*/

return [

    /* ---------- Voci brevi: incluso/escluso, pasti, luoghi ---------- */

    'Frutta Mista' => 'Fruta variada',
    'Frutta mista' => 'Fruta variada',
    'Light Lunch' => 'Almuerzo ligero',
    'Light lunch' => 'Almuerzo ligero',
    'Aperitivo' => 'Aperitivo',
    'Acqua' => 'Agua',
    'Caffè' => 'Café',
    'Acqua e Caffè illimitato' => 'Agua y café ilimitados',
    'Due Calici di Prosecco o Vino' => 'Dos copas de Prosecco o vino',
    'Pizza Focaccia' => 'Pizza focaccia',
    'Pinsa' => 'Pinsa',
    'Panini' => 'Bocadillos',
    'Beverage' => 'Bebidas',
    'Taralli, Olive e Pane Guttiau' => 'Taralli, aceitunas y pan Guttiau',
    'Spuntino (10:30 – 11:30) — Frutta fresca' => 'Tentempié (10:30 – 11:30) — Fruta fresca',
    'Light Lunch (13:00 – 14:00) — Insalata di farro, gamberetti e zucchine; Fragola con stracciatella, datterini e olio al basilico; Chicken Salad' => 'Almuerzo ligero (13:00 – 14:00) — Ensalada de espelta con gambas y calabacín; Fresa con stracciatella, tomates cherry y aceite de albahaca; Ensalada de pollo',
    'Aperitivo (16:00) — Prosecco, olive, taralli, pane Guttiau' => 'Aperitivo (16:00) — Prosecco, aceitunas, taralli, pan Guttiau',
    'Su richiesta' => 'Bajo petición',
    'Porto di partenza' => 'Puerto de salida',
    'Fascia bambini' => 'Niños',
    'Fascia 0-12' => 'De 0 a 12 años',
    'Fascia 13 in poi' => 'A partir de 13 años',
    '0 - 7' => '0 - 7',
    '8 - 14' => '8 - 14',
    '15 in poi' => '15 y más',
    'Crociera giornaliere tra acque cristalline' => 'Crucero de un día entre aguas cristalinas',
    'Crociera fullday tra acque cristalline' => 'Crucero de día completo entre aguas cristalinas',

    /* ---------- Solarya Daily Escape ---------- */

    'Salpa con noi e lasciati conquistare dalla magia della Sardegna! Un\'esperienza esclusiva tra comfort, natura e libertà, pensata per chi vuole vivere il mare da protagonista e portare a casa ricordi indimenticabili.' => '¡Zarpa con nosotros y déjate conquistar por la magia de Cerdeña! Una experiencia exclusiva entre confort, naturaleza y libertad, pensada para quien quiere vivir el mar como protagonista y llevarse a casa recuerdos inolvidables.',

    'La prima sosta meravigliosa che ti aspetta è nelle celebri Piscina di Molara, un angolo di paradiso famoso per i suoi fondali cristallini e i colori incredibili dell\'acqua, perfetti per una nuotata rigenerante o per fare snorkeling tra pesci e natura incontaminata.' => 'La primera parada maravillosa que te espera son las célebres Piscine di Molara, un rincón de paraíso famoso por sus fondos cristalinos y los increíbles colores del agua, perfectos para un baño revitalizante o para hacer snorkel entre peces y naturaleza virgen.',

    'Proseguiremo la navigazione verso la suggestiva Spiaggia delle Vacche, piccola e riservata, con sabbia chiara e rocce granitiche scolpite dal vento e rimarrai incantato da Cala Girgolu, una baia dal fascino selvaggio e raffinato, celebre per le sue rocce dalle forme curiose e il mare trasparente che invita a tuffarsi.' => 'Continuaremos la navegación hacia la sugerente Spiaggia delle Vacche, pequeña y recogida, con arena clara y rocas de granito esculpidas por el viento, y te quedarás encantado con Cala Girgolu, una bahía de encanto salvaje y refinado, célebre por sus rocas de formas curiosas y su mar transparente que invita a lanzarse al agua.',

    'La seconda sosta continua davanti al promontorio di Capo Coda Cavallo, uno dei punti panoramici più belli della costa nord-orientale, da cui si gode una vista mozzafiato sull\'Area Marina Protetta di Tavolara.' => 'La segunda parada tiene lugar frente al promontorio de Capo Coda Cavallo, uno de los miradores más bonitos de la costa nordeste, desde donde se disfruta de una vista impresionante del Área Marina Protegida de Tavolara.',

    'Infine raggiungeremo per l\'ultima sosta la splendida Cala Brandinchi, conosciuta come la "Piccola Tahiti" per la sua sabbia bianca finissima e il mare dalle sfumature caraibiche, il luogo perfetto per concludere la giornata tra sole, sorrisi e pura meraviglia.' => 'Por último, para la parada final llegaremos a la espléndida Cala Brandinchi, conocida como la «Pequeña Tahití» por su arena blanca finísima y su mar de tonos caribeños: el lugar perfecto para terminar el día entre sol, sonrisas y pura maravilla.',

    'Piscina di Molara (sosta) – Cala Girgolu & Spiagge delle Vacche (navigazione) – Capo Coda Cavallo (sosta) – Cala Brandinchi (sosta)' => 'Piscine di Molara (parada) – Cala Girgolu y Spiagge delle Vacche (navegación) – Capo Coda Cavallo (parada) – Cala Brandinchi (parada)',

    /* ---------- Solarya Sunset Escape ---------- */

    'Il tramonto più bello della Costa' => 'La puesta de sol más bonita de la costa',

    'Vivi la magia del tramonto in Sardegna con un\'esclusiva escursione in catamarano tra la splendida spiaggia La Cinta e l\'elegante cornice di Puntaldia, due dei luoghi più affascinanti della costa di San Teodoro.' => 'Vive la magia del atardecer en Cerdeña con una exclusiva excursión en catamarán entre la espléndida playa de La Cinta y el elegante entorno de Puntaldia, dos de los lugares más fascinantes de la costa de San Teodoro.',

    'Quando il sole inizia a scendere lentamente sull\'orizzonte, il mare si accende di riflessi dorati e il cielo si tinge di sfumature rosa, arancio e viola, regalando uno spettacolo naturale che lascia senza fiato.' => 'Cuando el sol empieza a descender lentamente sobre el horizonte, el mar se enciende con reflejos dorados y el cielo se tiñe de rosa, naranja y violeta, regalando un espectáculo natural que deja sin aliento.',

    'A bordo del nostro elegante catamarano potrai rilassarti cullato dalle onde, sorseggiare un aperitivo fronte mare e lasciarti avvolgere dall\'atmosfera unica di questo tratto di costa, con vista sull\'imponente profilo dell\'isola di Tavolara che rende il panorama ancora più suggestivo.' => 'A bordo de nuestro elegante catamarán podrás relajarte acunado por las olas, tomar un aperitivo frente al mar y dejarte envolver por la atmósfera única de este tramo de costa, con vistas al imponente perfil de la isla de Tavolara, que hace el panorama aún más sugerente.',

    'Navigheremo dolcemente tra la fine della spiaggia La Cinta e le acque cristalline di Puntaldia, in uno scenario esclusivo fatto di natura incontaminata, mare trasparente e tramonti indimenticabili. Avrai la possibilità di tuffarti nelle acque calme illuminate dagli ultimi raggi del sole, o semplicemente goderti il silenzio del mare al calar della sera.' => 'Navegaremos suavemente entre el final de la playa de La Cinta y las aguas cristalinas de Puntaldia, en un escenario exclusivo de naturaleza virgen, mar transparente y atardeceres inolvidables. Tendrás la posibilidad de bañarte en las aguas tranquilas iluminadas por los últimos rayos de sol, o simplemente disfrutar del silencio del mar al caer la tarde.',

    'Ogni istante sarà un\'esperienza da vivere intensamente, lontano dalla folla e immerso in una delle atmosfere più romantiche della Sardegna.' => 'Cada instante será una experiencia para vivir intensamente, lejos de las multitudes y sumergido en una de las atmósferas más románticas de Cerdeña.',

    'Quando il sole scomparirà dietro l\'orizzonte, il catamarano diventerà il luogo perfetto per condividere emozioni speciali: una serata romantica, un brindisi con amici o un ricordo unico della tua vacanza.' => 'Cuando el sol desaparezca tras el horizonte, el catamarán se convertirá en el lugar perfecto para compartir emociones especiales: una velada romántica, un brindis con amigos o un recuerdo único de tus vacaciones.',

    'Un\'esperienza elegante, rilassante e incredibilmente suggestiva, pensata per chi desidera vivere il mare da una prospettiva esclusiva e lasciarsi conquistare dalla magia del tramonto tra La Cinta e Puntaldia.' => 'Una experiencia elegante, relajante e increíblemente sugerente, pensada para quien desea vivir el mar desde una perspectiva exclusiva y dejarse conquistar por la magia del atardecer entre La Cinta y Puntaldia.',

    /* ---------- Solarya Private Cruise ---------- */

    'Non prenotare un posto ✨ Prenota l\'intero mare 🌊' => 'No reserves una plaza ✨ Reserva el mar entero 🌊',

    'Vivi la Sardegna più autentica da una prospettiva privilegiata.' => 'Vive la Cerdeña más auténtica desde una perspectiva privilegiada.',

    'Con la nostra escursione privata in esclusiva, il catamarano sarà interamente riservato a te e ai tuoi ospiti, per una giornata all\'insegna del relax, del comfort e della libertà assoluta.' => 'Con nuestra excursión privada en exclusiva, el catamarán quedará enteramente reservado para ti y tus invitados, para un día dedicado al relax, el confort y la libertad absoluta.',

    'Nessuna folla, nessun programma rigido: solo il piacere di scegliere i tuoi ritmi, fermarti dove desideri e goderti ogni istante circondato dal mare.' => 'Sin multitudes, sin programas rígidos: solo el placer de elegir tu ritmo, detenerte donde quieras y disfrutar de cada instante rodeado de mar.',

    'Che si tratti di una ricorrenza speciale, di una giornata romantica, di un\'esperienza in famiglia o di un evento privato con amici, il nostro equipaggio si prenderà cura di ogni dettaglio per offrirti un servizio personalizzato e un\'esperienza indimenticabile.' => 'Ya sea una ocasión especial, un día romántico, una experiencia en familia o un evento privado con amigos, nuestra tripulación cuidará cada detalle para ofrecerte un servicio personalizado y una experiencia inolvidable.',

    'Lasciati cullare dal vento, tuffati nelle acque turchesi delle baie più esclusive e scopri il fascino unico di San Teodoro e dell\'Area Marina Protetta di Tavolara a bordo di un elegante catamarano riservato solo a te.' => 'Déjate acunar por el viento, sumérgete en las aguas turquesas de las calas más exclusivas y descubre el encanto único de San Teodoro y del Área Marina Protegida de Tavolara a bordo de un elegante catamarán reservado solo para ti.',

    'La tua Sardegna esclusiva inizia qui.' => 'Tu Cerdeña exclusiva empieza aquí.',

];
