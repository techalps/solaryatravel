<?php

/*
| Vedi lang/it/emails.php per la struttura e le note.
| I nomi dei :placeholder devono restare identici in ogni lingua.
*/

return [

    'common' => [
        'greeting' => 'Bonjour :name,',
        'booking' => 'Réservation',
        'tour' => 'Excursion',
        'date_departure' => 'Date et départ',
        'date' => 'Date',
        'participants' => 'Participants',
        'passengers' => 'Passagers',
        'total' => 'Total',
        'questions' => 'Pour toute question, il vous suffit de répondre à cet e-mail.',
        'rights' => 'Tous droits réservés',
        'footer_help' => 'Des questions ? Répondez à cet e-mail ou contactez notre équipe.',
    ],

    'payment_link' => [
        'subject' => 'Finalisez le paiement de votre réservation n° :number',
        'title' => 'Confirmez et payez votre réservation',
        'intro' => 'merci d\'avoir réservé chez nous ! Pour finaliser votre excursion :tour, réglez le paiement en ligne en toute sécurité via Stripe.',
        'total_due' => 'Total à payer',
        'cta' => 'Payer par carte',
        'fallback' => 'Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :',
        'deadline' => 'Finalisez le paiement avant le :date, faute de quoi la réservation expirera et les places seront remises en vente.',
        'after_payment' => 'Une fois le paiement effectué, vous recevrez un second e-mail avec vos billets et les QR codes à présenter à l\'embarquement (un par passager).',
        'important' => 'Important :',
        'link_expires' => 'Le lien de paiement expire le :date.',
    ],

    'awaiting_transfer' => [
        'subject' => 'Instructions pour le virement · Réservation n° :number',
        'title' => 'Finalisez votre paiement par virement',
        'intro' => 'nous avons enregistré votre réservation n° :number pour :tour. Pour la confirmer, effectuez un virement avec les coordonnées ci-dessous.',
        'amount' => 'Montant à virer',
        'bank_details' => 'Coordonnées bancaires',
        'reference' => 'Référence : :number',
        'after' => 'Dès réception du virement, nous confirmerons la réservation et vous enverrons les billets par e-mail.',
        'hint' => 'Indiquez toujours le numéro de réservation dans la référence pour accélérer la vérification.',
    ],

    'balance_reminder' => [
        'subject' => 'Réglez le solde de votre réservation n° :number',
        'title' => 'Finalisez le paiement du solde',
        'intro' => 'la date de votre excursion :tour approche. Pour finaliser la réservation n° :number, nous vous rappelons de régler le montant restant.',
        'deposit_paid' => 'Acompte versé',
        'balance_due' => 'Solde à payer',
        'deadline' => '⏰ À régler avant le :date.',
        'cta' => 'Payer le solde',
    ],

    'tickets' => [
        'subject' => 'Vos billets · Réservation n° :number',
        'title' => 'Paiement confirmé — voici vos billets !',
        'intro' => 'votre paiement a bien été effectué. Vous trouverez en pièce jointe le PDF contenant les billets de tous les passagers.',
        'instructions' => 'Imprimez-le ou présentez-le depuis votre téléphone au moment de l\'embarquement : chaque billet comporte un QR code qui sera scanné pour enregistrer la présence.',
        'attachment' => '📎 Pièce jointe : biglietti-:number.pdf',
        'tip_label' => 'Conseil :',
        'tip' => 'présentez-vous au ponton au moins 15 minutes avant le départ avec le PDF des billets (imprimé ou sur votre téléphone).',
    ],

    'reminder_48h' => [
        'subject' => 'Excursion dans 2 jours — Réservation :number',
        'eyebrow' => 'Rappel · 48 heures',
        'title' => 'Votre excursion approche !',
        'intro_with_time' => 'votre excursion :tour est prévue le :date à :time.',
        'intro_without_time' => 'votre excursion :tour est prévue le :date.',
        'instructions' => 'Nous vous rappelons de vous présenter au ponton au moins 15 minutes avant le départ avec le PDF des billets (joint à l\'e-mail de confirmation), imprimé ou sur votre téléphone. Le QR code de chaque billet sera scanné à l\'embarquement.',
        'closing' => 'Bon voyage !',
    ],

    'reminder_24h' => [
        'subject' => 'Départ demain ! Rappel d\'enregistrement - :number',
        'eyebrow' => 'Départ demain',
        'title' => '🌊 Tout est prêt pour votre excursion ?',
        'intro' => 'vous partez demain ! Voici un récapitulatif de votre réservation et des participants enregistrés.',
        'registered' => 'Participants enregistrés (:count)',
        'lead_booker' => 'Titulaire de la réservation',
        'tax_code' => 'N° fiscal',
    ],

    'cancelled' => [
        'subject' => 'Réservation annulée · n° :number',
        'title' => 'Réservation annulée',
        'intro' => 'nous vous confirmons que votre réservation n° :number a été annulée.',
        'cancelled_at' => 'Annulée le',
        'reason' => 'Motif',
        'refund_detail' => 'Détail du remboursement',
        'amount_paid' => 'Montant versé',
        'refund' => 'Remboursement (:percentage %)',
        'penalty' => 'Pénalité retenue',
    ],

    'refunded' => [
        'subject' => 'Remboursement effectué · Réservation n° :number',
        'title' => 'Remboursement effectué',
        'intro' => 'nous vous confirmons que le remboursement de la réservation n° :number a été effectué.',
        'timing' => 'Le crédit sur la carte utilisée pour le paiement peut prendre de 5 à 10 jours ouvrés, selon le réseau bancaire.',
        'amount' => 'Montant remboursé',
        'penalty' => 'Pénalité d\'annulation',
        'booked_date' => 'Date réservée',
        'note' => 'Remarque',
        'closing' => 'Pour toute question sur le crédit ou les délais, répondez simplement à cet e-mail.',
    ],

    'welcome' => [
        'subject' => 'Bienvenue sur :app',
    ],

];
