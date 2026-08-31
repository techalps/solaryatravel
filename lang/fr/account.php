<?php

/*
| Vedi lang/it/account.php per la struttura e le note.
| I nomi dei :placeholder devono restare identici in ogni lingua.
*/

return [

    'common' => [
        'booking' => 'Réservation',
        'home' => 'Accueil',
        'your_tour' => 'Votre excursion',
        'summary' => 'Récapitulatif',
        'order_summary' => 'Récapitulatif de la commande',
        'amounts_summary' => 'Récapitulatif des montants',
        'subtotal' => 'Sous-total',
        'extras' => 'Extras',
        'discount' => 'Remise',
        'vat' => 'TVA',
        'vat_included' => 'TVA incluse',
        'total' => 'Total',
        'total_colon' => 'Total :',
        'status' => 'Statut',
        'duration' => 'Durée',
        'date' => 'Date',
        'method' => 'Mode',
        'payment' => 'Paiement',
        'lead_booker' => 'Titulaire',
        'name' => 'Prénom',
        'email' => 'E-mail',
        'phone' => 'Téléphone',
        'special_requests' => 'Demandes particulières',
        'participants' => 'Participants',
        'person' => ':count personne|:count personnes',
        'participant' => ':count participant|:count participants',
        'seat' => ':count place|:count places',
        'hours' => ':count heures',
        'hours_short' => ':count h',
        'adult' => 'Adulte',
        'participants_count' => 'Participants',
        'deadline' => 'Échéance',
        'actions' => 'Actions',
        'cancelled_item' => 'Annulé',
        'boarded' => 'Embarqué',
    ],

    'status_labels' => [
        'pending' => 'En attente de paiement',
        'deposit_paid' => 'Acompte versé',
        'awaiting_transfer' => 'En attente de virement',
        'confirmed' => 'Confirmée',
        'checked_in' => 'Enregistré',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
        'refunded' => 'Remboursée',
        'no_show' => 'Absence',
    ],

    'pay' => [
        'title' => 'Confirmer et payer',
        'awaiting' => 'en attente de paiement',
        'lead_booker_title' => 'Titulaire de la réservation',
        'extras_included' => 'Extras inclus :',
        'seats_held_for' => 'Places réservées pendant',
        'secure_payment' => 'Paiement sécurisé',
        'stripe_note' => 'Paiement géré par Stripe — vos données ne transitent pas par notre serveur.',
        'instant_confirm' => 'Confirmation immédiate et reçu envoyé par e-mail.',
        'tickets_after' => 'Billets avec QR code délivrés juste après le paiement.',
    ],

    'success' => [
        'title' => 'Réservation confirmée !',
        'intro' => ':name, nous avons bien reçu votre paiement.',
        'deposit_title' => 'Acompte reçu',
        'deposit_intro' => 'Merci ! Nous avons enregistré votre acompte. Votre place est confirmée.',
        'deposit_tickets_later' => 'Vous recevrez vos billets par e-mail une fois le solde réglé.',
        'by_date' => 'avant le :date',
        'pay_balance' => 'Payer le solde',
        'sent_to' => 'Nous avons envoyé les billets et le reçu à',
        'all_tickets' => 'Billets de tous les participants',
        'total_paid' => 'Total payé',
        'back_home' => 'Retour à l\'accueil',
        'explore_tours' => 'Découvrir d\'autres excursions',
    ],

    'cancel' => [
        'title' => 'Paiement annulé',
        'intro' => 'Votre réservation est toujours en attente — vous pouvez réessayer quand vous le souhaitez.',
        'no_charge' => 'Aucun montant n\'a été débité. La réservation reste valable jusqu\'à son expiration.',
        'total_due' => 'Total à payer :',
    ],

    'balance' => [
        'title' => 'Paiement du solde',
        'booking_total' => 'Total de la réservation',
        'deposit_paid' => 'Acompte versé',
        'balance' => 'Solde',
        'balance_due' => 'Solde à payer',
        'secure_stripe' => 'Paiement sécurisé via Stripe',
        'complete_payment' => 'Finaliser le paiement',
    ],

    'transfer' => [
        'registered' => 'Réservation enregistrée',
        'title' => 'Finalisez votre paiement par virement instantané',
        'intro' => 'La réservation :number est en attente de paiement et les places sont réservées. Dès réception du virement, nous la confirmerons et vous recevrez vos billets par e-mail.',
        'amount' => 'Montant à virer',
        'deposit_then_balance' => 'Acompte · solde ultérieur',
        'bank_details' => 'Coordonnées bancaires',
        'reference_hint' => 'Indiquez le numéro de réservation comme :reference :',
        'reference_word' => 'référence',
        'go_to_booking' => 'Voir ma réservation',
        'transfer' => 'Virement',
    ],

    'my_bookings' => [
        'title' => 'Mes réservations',
        'subtitle' => 'Historique, prochains départs et billets de vos excursions.',
        'empty_title' => 'Aucune réservation pour le moment',
        'empty_text' => 'Découvrez nos excursions en catamaran le long de la côte. Votre prochaine expérience commence ici.',
        'today' => 'Aujourd\'hui',
        'upcoming' => 'À venir',
        'user_fallback' => 'Utilisateur',
    ],

    'detail' => [
        'booked_extras' => 'Extras réservés',
        'passenger_todo' => '— Informations à compléter —',
        'extra_fallback' => 'Extra',
        'tour_fallback' => 'Excursion',
    ],

    'tickets' => [
        'title' => 'Vos billets',
        'one_per_passenger' => 'Chaque passager a son propre billet.',
        'passenger' => 'Passager',
        'adult' => 'Adulte',
        'seat' => 'Place',
        'catamaran' => 'Catamaran',
        'departure' => 'Départ',
        'boarding_point' => 'Point d\'embarquement',
        'boarded' => 'EMBARQUÉ',
        'tour' => 'Excursion',
    ],

];
