{{-- Union Jack (SVG inline: vedi flags/it.blade.php per il perché non usiamo
     l'emoji). Accompagnata SEMPRE dalla sigla "EN" nel selettore: la bandiera
     è un paese, la lingua inglese non ne ha una.

     Costruzione (dal basso): campo blu → croce di Sant'Andrea bianca →
     croce di San Patrizio rossa (mezze diagonali, per questo il tratto rosso
     è disegnato come quattro segmenti e non due linee piene) → croce di
     San Giorgio bianca e poi rossa. --}}
<svg class="tg-lang__flag" viewBox="0 0 60 30" aria-hidden="true" focusable="false">
    <rect width="60" height="30" fill="#012169"/>
    {{-- Sant'Andrea: diagonali bianche piene --}}
    <path d="M0 0 60 30M60 0 0 30" stroke="#fff" stroke-width="6"/>
    {{-- San Patrizio: metà diagonali rosse, ruotate attorno al centro --}}
    <path d="M0 0 30 15M60 0 30 15" stroke="#c8102e" stroke-width="2" transform="translate(0 -1)"/>
    <path d="M30 15 0 30M30 15 60 30" stroke="#c8102e" stroke-width="2" transform="translate(0 1)"/>
    {{-- San Giorgio: croce centrale bianca, poi rossa --}}
    <path d="M30 0V30M0 15H60" stroke="#fff" stroke-width="10"/>
    <path d="M30 0V30M0 15H60" stroke="#c8102e" stroke-width="6"/>
</svg>
