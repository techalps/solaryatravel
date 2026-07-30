<?php

/*
| Vedi lang/it/emails.php per la struttura e le note.
| I nomi dei :placeholder devono restare identici in ogni lingua.
*/

return [

    'common' => [
        'greeting' => 'Hi :name,',
        'booking' => 'Booking',
        'tour' => 'Tour',
        'date_departure' => 'Date and departure',
        'date' => 'Date',
        'participants' => 'Participants',
        'passengers' => 'Passengers',
        'total' => 'Total',
        'questions' => 'If you have any questions, just reply to this email.',
        'rights' => 'All rights reserved',
        'footer_help' => 'Questions? Reply to this email or contact our staff.',
    ],

    'payment_link' => [
        'subject' => 'Complete the payment for your booking #:number',
        'title' => 'Confirm and pay for your booking',
        'intro' => 'thank you for booking with us! To finalise your :tour excursion, complete the payment securely online via Stripe.',
        'total_due' => 'Total to pay',
        'cta' => 'Pay now by card',
        'fallback' => 'If the button does not work, copy and paste this link into your browser:',
        'deadline' => 'Complete the payment by :date, otherwise the booking expires and the seats are released.',
        'after_payment' => 'Once the payment is complete, you will receive a second email with your tickets and the QR codes to show at boarding (one per passenger).',
        'important' => 'Important:',
        'link_expires' => 'The payment link expires on :date.',
    ],

    'awaiting_transfer' => [
        'subject' => 'Bank transfer instructions · Booking #:number',
        'title' => 'Complete your payment by bank transfer',
        'intro' => 'we have registered your booking #:number for :tour. To confirm it, please make a bank transfer using the details below.',
        'amount' => 'Amount to transfer',
        'bank_details' => 'Bank details',
        'reference' => 'Reference: :number',
        'after' => 'As soon as we receive the transfer, we will confirm the booking and send you the tickets by email.',
        'hint' => 'Always include the booking number in the reference to speed up the check.',
    ],

    'balance_reminder' => [
        'subject' => 'Pay the balance of your booking #:number',
        'title' => 'Complete your balance payment',
        'intro' => 'the date of your :tour tour is approaching. To complete booking #:number, please remember to pay the outstanding amount.',
        'deposit_paid' => 'Deposit paid',
        'balance_due' => 'Balance to pay',
        'deadline' => '⏰ Pay by :date.',
        'cta' => 'Pay the balance',
    ],

    'tickets' => [
        'subject' => 'Your tickets · Booking #:number',
        'title' => 'Payment confirmed — here are your tickets!',
        'intro' => 'your payment went through. Attached to this email you will find the PDF with the tickets for all passengers.',
        'instructions' => 'Print it or show it on your phone when boarding: each ticket has a QR code that will be scanned to register attendance.',
        'attachment' => '📎 Attached: biglietti-:number.pdf',
        'tip_label' => 'Tip:',
        'tip' => 'arrive at the pier at least 15 minutes before departure with the tickets PDF (printed or on your phone).',
    ],

    'reminder_48h' => [
        'subject' => 'Tour in 2 days — Booking :number',
        'eyebrow' => 'Reminder · 48 hours',
        'title' => 'Your tour is coming up!',
        'intro_with_time' => 'your :tour tour is scheduled for :date at :time.',
        'intro_without_time' => 'your :tour tour is scheduled for :date.',
        'instructions' => 'Please arrive at the pier at least 15 minutes before departure with the tickets PDF (attached to the confirmation email) — printed or on your phone. The QR code on each ticket will be scanned at boarding.',
        'closing' => 'Have a great trip!',
    ],

    'reminder_24h' => [
        'subject' => 'You set sail tomorrow! Check-in reminder - :number',
        'eyebrow' => 'Departure tomorrow',
        'title' => '🌊 All set for your tour?',
        'intro' => 'you set sail tomorrow! Here is a summary of your booking and the registered participants.',
        'registered' => 'Registered participants (:count)',
        'lead_booker' => 'Lead booker',
        'tax_code' => 'Tax code',
    ],

    'cancelled' => [
        'subject' => 'Booking cancelled · #:number',
        'title' => 'Booking cancelled',
        'intro' => 'we confirm that your booking #:number has been cancelled.',
        'cancelled_at' => 'Cancelled on',
        'reason' => 'Reason',
        'refund_detail' => 'Refund details',
        'amount_paid' => 'Amount paid',
        'refund' => 'Refund (:percentage%)',
        'penalty' => 'Penalty withheld',
    ],

    'refunded' => [
        'subject' => 'Refund issued · Booking #:number',
        'title' => 'Refund issued',
        'intro' => 'we confirm that the refund for booking #:number has been issued.',
        'timing' => 'The credit on the card used for the payment may take up to 5–10 working days, depending on the card network.',
        'amount' => 'Amount refunded',
        'penalty' => 'Cancellation penalty',
        'booked_date' => 'Booked date',
        'note' => 'Note',
        'closing' => 'If you have any questions about the credit or the timing, just reply to this email.',
    ],

    'welcome' => [
        'subject' => 'Welcome to :app',
    ],

];
