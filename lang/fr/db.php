<?php

/*
|--------------------------------------------------------------------------
| Dizionario contenuti da database (IT → FR)
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
| Verifica la copertura con: php artisan i18n:missing --locale=fr
|
*/

return [

    /* ---------- Voci brevi: incluso/escluso, pasti, luoghi ---------- */

    'Frutta Mista' => 'Fruits variés',
    'Frutta mista' => 'Fruits variés',
    'Light Lunch' => 'Déjeuner léger',
    'Light lunch' => 'Déjeuner léger',
    'Aperitivo' => 'Apéritif',
    'Acqua' => 'Eau',
    'Caffè' => 'Café',
    'Acqua e Caffè illimitato' => 'Eau et café à volonté',
    'Due Calici di Prosecco o Vino' => 'Deux verres de Prosecco ou de vin',
    'Pizza Focaccia' => 'Pizza focaccia',
    'Pinsa' => 'Pinsa',
    'Panini' => 'Sandwichs',
    'Beverage' => 'Boissons',
    'Taralli, Olive e Pane Guttiau' => 'Taralli, olives et pain Guttiau',
    'Spuntino (10:30 – 11:30) — Frutta fresca' => 'Collation (10:30 – 11:30) — Fruits frais',
    'Light Lunch (13:00 – 14:00) — Insalata di farro, gamberetti e zucchine; Fragola con stracciatella, datterini e olio al basilico; Chicken Salad' => 'Déjeuner léger (13:00 – 14:00) — Salade d\'épeautre, crevettes et courgettes ; Fraise et stracciatella, tomates cerises et huile de basilic ; Salade de poulet',
    'Aperitivo (16:00) — Prosecco, olive, taralli, pane Guttiau' => 'Apéritif (16:00) — Prosecco, olives, taralli, pain Guttiau',
    'Su richiesta' => 'Sur demande',
    'Porto di partenza' => 'Port de départ',
    'Fascia bambini' => 'Enfants',
    'Fascia 0-12' => 'De 0 à 12 ans',
    'Fascia 13 in poi' => 'À partir de 13 ans',
    '0 - 7' => '0 - 7',
    '8 - 14' => '8 - 14',
    '15 in poi' => '15 et plus',
    'Crociera giornaliere tra acque cristalline' => 'Croisière à la journée dans des eaux cristallines',
    'Crociera fullday tra acque cristalline' => 'Croisière journée complète dans des eaux cristallines',

    /* ---------- Solarya Daily Escape ---------- */

    'Salpa con noi e lasciati conquistare dalla magia della Sardegna! Un\'esperienza esclusiva tra comfort, natura e libertà, pensata per chi vuole vivere il mare da protagonista e portare a casa ricordi indimenticabili.' => 'Prenez le large avec nous et laissez-vous conquérir par la magie de la Sardaigne ! Une expérience exclusive entre confort, nature et liberté, pensée pour celles et ceux qui veulent vivre la mer en acteur et rapporter des souvenirs inoubliables.',

    'La prima sosta meravigliosa che ti aspetta è nelle celebri Piscina di Molara, un angolo di paradiso famoso per i suoi fondali cristallini e i colori incredibili dell\'acqua, perfetti per una nuotata rigenerante o per fare snorkeling tra pesci e natura incontaminata.' => 'La première escale merveilleuse qui vous attend, ce sont les célèbres Piscine di Molara, un coin de paradis réputé pour ses fonds cristallins et les couleurs incroyables de l\'eau, parfaits pour une baignade régénérante ou pour faire du snorkeling parmi les poissons et une nature intacte.',

    'Proseguiremo la navigazione verso la suggestiva Spiaggia delle Vacche, piccola e riservata, con sabbia chiara e rocce granitiche scolpite dal vento e rimarrai incantato da Cala Girgolu, una baia dal fascino selvaggio e raffinato, celebre per le sue rocce dalle forme curiose e il mare trasparente che invita a tuffarsi.' => 'Nous poursuivrons la navigation vers la charmante Spiaggia delle Vacche, petite et préservée, avec son sable clair et ses rochers de granit sculptés par le vent, et vous serez émerveillé par Cala Girgolu, une baie au charme sauvage et raffiné, célèbre pour ses rochers aux formes curieuses et sa mer transparente qui invite à la baignade.',

    'La seconda sosta continua davanti al promontorio di Capo Coda Cavallo, uno dei punti panoramici più belli della costa nord-orientale, da cui si gode una vista mozzafiato sull\'Area Marina Protetta di Tavolara.' => 'La deuxième escale se déroule face au promontoire de Capo Coda Cavallo, l\'un des plus beaux points de vue de la côte nord-est, d\'où l\'on profite d\'un panorama à couper le souffle sur l\'Aire Marine Protégée de Tavolara.',

    'Infine raggiungeremo per l\'ultima sosta la splendida Cala Brandinchi, conosciuta come la "Piccola Tahiti" per la sua sabbia bianca finissima e il mare dalle sfumature caraibiche, il luogo perfetto per concludere la giornata tra sole, sorrisi e pura meraviglia.' => 'Enfin, pour la dernière escale, nous rejoindrons la splendide Cala Brandinchi, surnommée « la petite Tahiti » pour son sable blanc très fin et sa mer aux nuances caribéennes : l\'endroit parfait pour conclure la journée entre soleil, sourires et pur émerveillement.',

    'Piscina di Molara (sosta) – Cala Girgolu & Spiagge delle Vacche (navigazione) – Capo Coda Cavallo (sosta) – Cala Brandinchi (sosta)' => 'Piscine di Molara (escale) – Cala Girgolu et Spiagge delle Vacche (navigation) – Capo Coda Cavallo (escale) – Cala Brandinchi (escale)',

    /* ---------- Solarya Sunset Escape ---------- */

    'Il tramonto più bello della Costa' => 'Le plus beau coucher de soleil de la côte',

    'Vivi la magia del tramonto in Sardegna con un\'esclusiva escursione in catamarano tra la splendida spiaggia La Cinta e l\'elegante cornice di Puntaldia, due dei luoghi più affascinanti della costa di San Teodoro.' => 'Vivez la magie du coucher de soleil en Sardaigne lors d\'une excursion exclusive en catamaran entre la splendide plage de La Cinta et le cadre élégant de Puntaldia, deux des lieux les plus fascinants de la côte de San Teodoro.',

    'Quando il sole inizia a scendere lentamente sull\'orizzonte, il mare si accende di riflessi dorati e il cielo si tinge di sfumature rosa, arancio e viola, regalando uno spettacolo naturale che lascia senza fiato.' => 'Lorsque le soleil commence à descendre lentement sur l\'horizon, la mer s\'illumine de reflets dorés et le ciel se teinte de rose, d\'orange et de violet, offrant un spectacle naturel à couper le souffle.',

    'A bordo del nostro elegante catamarano potrai rilassarti cullato dalle onde, sorseggiare un aperitivo fronte mare e lasciarti avvolgere dall\'atmosfera unica di questo tratto di costa, con vista sull\'imponente profilo dell\'isola di Tavolara che rende il panorama ancora più suggestivo.' => 'À bord de notre élégant catamaran, vous pourrez vous détendre bercé par les vagues, savourer un apéritif face à la mer et vous laisser envelopper par l\'atmosphère unique de cette portion de côte, avec vue sur l\'imposante silhouette de l\'île de Tavolara qui rend le panorama encore plus saisissant.',

    'Navigheremo dolcemente tra la fine della spiaggia La Cinta e le acque cristalline di Puntaldia, in uno scenario esclusivo fatto di natura incontaminata, mare trasparente e tramonti indimenticabili. Avrai la possibilità di tuffarti nelle acque calme illuminate dagli ultimi raggi del sole, o semplicemente goderti il silenzio del mare al calar della sera.' => 'Nous naviguerons doucement entre l\'extrémité de la plage de La Cinta et les eaux cristallines de Puntaldia, dans un décor exclusif fait de nature intacte, de mer transparente et de couchers de soleil inoubliables. Vous pourrez plonger dans les eaux calmes éclairées par les derniers rayons du soleil, ou simplement profiter du silence de la mer à la tombée du soir.',

    'Ogni istante sarà un\'esperienza da vivere intensamente, lontano dalla folla e immerso in una delle atmosfere più romantiche della Sardegna.' => 'Chaque instant sera une expérience à vivre intensément, loin de la foule et plongé dans l\'une des atmosphères les plus romantiques de la Sardaigne.',

    'Quando il sole scomparirà dietro l\'orizzonte, il catamarano diventerà il luogo perfetto per condividere emozioni speciali: una serata romantica, un brindisi con amici o un ricordo unico della tua vacanza.' => 'Quand le soleil disparaîtra derrière l\'horizon, le catamaran deviendra l\'endroit idéal pour partager des émotions particulières : une soirée romantique, un toast entre amis ou un souvenir unique de vos vacances.',

    'Un\'esperienza elegante, rilassante e incredibilmente suggestiva, pensata per chi desidera vivere il mare da una prospettiva esclusiva e lasciarsi conquistare dalla magia del tramonto tra La Cinta e Puntaldia.' => 'Une expérience élégante, relaxante et incroyablement évocatrice, pensée pour celles et ceux qui souhaitent vivre la mer sous un angle exclusif et se laisser conquérir par la magie du coucher de soleil entre La Cinta et Puntaldia.',

    /* ---------- Solarya Private Cruise ---------- */

    'Non prenotare un posto ✨ Prenota l\'intero mare 🌊' => 'Ne réservez pas une place ✨ Réservez la mer entière 🌊',

    'Vivi la Sardegna più autentica da una prospettiva privilegiata.' => 'Vivez la Sardaigne la plus authentique depuis une perspective privilégiée.',

    'Con la nostra escursione privata in esclusiva, il catamarano sarà interamente riservato a te e ai tuoi ospiti, per una giornata all\'insegna del relax, del comfort e della libertà assoluta.' => 'Avec notre excursion privée en exclusivité, le catamaran sera entièrement réservé à vous et à vos invités, pour une journée placée sous le signe de la détente, du confort et d\'une liberté absolue.',

    'Nessuna folla, nessun programma rigido: solo il piacere di scegliere i tuoi ritmi, fermarti dove desideri e goderti ogni istante circondato dal mare.' => 'Pas de foule, pas de programme rigide : juste le plaisir de choisir votre rythme, de vous arrêter où vous le souhaitez et de profiter de chaque instant entouré par la mer.',

    'Che si tratti di una ricorrenza speciale, di una giornata romantica, di un\'esperienza in famiglia o di un evento privato con amici, il nostro equipaggio si prenderà cura di ogni dettaglio per offrirti un servizio personalizzato e un\'esperienza indimenticabile.' => 'Qu\'il s\'agisse d\'une occasion spéciale, d\'une journée romantique, d\'une expérience en famille ou d\'un événement privé entre amis, notre équipage prendra soin de chaque détail pour vous offrir un service personnalisé et une expérience inoubliable.',

    'Lasciati cullare dal vento, tuffati nelle acque turchesi delle baie più esclusive e scopri il fascino unico di San Teodoro e dell\'Area Marina Protetta di Tavolara a bordo di un elegante catamarano riservato solo a te.' => 'Laissez-vous bercer par le vent, plongez dans les eaux turquoise des baies les plus exclusives et découvrez le charme unique de San Teodoro et de l\'Aire Marine Protégée de Tavolara à bord d\'un élégant catamaran réservé à vous seul.',

    'La tua Sardegna esclusiva inizia qui.' => 'Votre Sardaigne exclusive commence ici.',

];
