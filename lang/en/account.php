<?php

/*
| Vedi lang/it/account.php per la struttura e le note.
| I nomi dei :placeholder devono restare identici in ogni lingua.
*/

return [

    'common' => [
        'booking' => 'Booking',
        'home' => 'Home',
        'your_tour' => 'Your tour',
        'summary' => 'Summary',
        'order_summary' => 'Order summary',
        'amounts_summary' => 'Payment summary',
        'subtotal' => 'Subtotal',
        'extras' => 'Extras',
        'discount' => 'Discount',
        'vat' => 'VAT',
        'vat_included' => 'VAT included',
        'total' => 'Total',
        'total_colon' => 'Total:',
        'status' => 'Status',
        'duration' => 'Duration',
        'date' => 'Date',
        'method' => 'Method',
        'payment' => 'Payment',
        'lead_booker' => 'Lead booker',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'special_requests' => 'Special requests',
        'participants' => 'Participants',
        'person' => ':count person|:count people',
        'participant' => ':count participant|:count participants',
        'seat' => ':count seat|:count seats',
        'hours' => ':count hours',
        'hours_short' => ':count h',
        'adult' => 'Adult',
        'participants_count' => 'Participants',
        'deadline' => 'Deadline',
        'actions' => 'Actions',
        'cancelled_item' => 'Cancelled',
        'boarded' => 'Checked in',
    ],

    'status_labels' => [
        'pending' => 'Awaiting payment',
        'deposit_paid' => 'Deposit paid',
        'awaiting_transfer' => 'Awaiting bank transfer',
        'confirmed' => 'Confirmed',
        'checked_in' => 'Checked in',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
        'no_show' => 'No show',
    ],

    'pay' => [
        'title' => 'Confirm and pay',
        'awaiting' => 'awaiting payment',
        'lead_booker_title' => 'Booking holder',
        'extras_included' => 'Extras included:',
        'seats_held_for' => 'Seats held for',
        'secure_payment' => 'Secure payment',
        'stripe_note' => 'Payment handled by Stripe — your details never pass through our server.',
        'instant_confirm' => 'Instant confirmation and receipt sent by email.',
        'tickets_after' => 'Tickets with QR codes delivered right after payment.',
    ],

    'success' => [
        'title' => 'Booking confirmed!',
        'intro' => ':name, we have received your payment.',
        'deposit_title' => 'Deposit received',
        'deposit_intro' => 'Thank you! We have recorded your deposit. Your seat is confirmed.',
        'deposit_tickets_later' => 'You will receive your tickets by email once the balance is paid.',
        'by_date' => 'by :date',
        'pay_balance' => 'Pay the balance',
        'sent_to' => 'We have sent the tickets and the receipt to',
        'all_tickets' => 'Tickets for all participants',
        'total_paid' => 'Total paid',
        'back_home' => 'Back to home',
        'explore_tours' => 'Explore other tours',
    ],

    'cancel' => [
        'title' => 'Payment cancelled',
        'intro' => 'Your booking is still pending — you can try again whenever you like.',
        'no_charge' => 'No amount has been charged. The booking remains valid until it expires.',
        'total_due' => 'Total due:',
    ],

    'balance' => [
        'title' => 'Balance payment',
        'booking_total' => 'Booking total',
        'deposit_paid' => 'Deposit paid',
        'balance' => 'Balance',
        'balance_due' => 'Balance due',
        'secure_stripe' => 'Secure payment via Stripe',
        'complete_payment' => 'Complete payment',
    ],

    'transfer' => [
        'registered' => 'Booking registered',
        'title' => 'Complete your payment by instant bank transfer',
        'intro' => 'Booking :number is awaiting payment and the seats are held. Once we receive the transfer we will confirm it and send your tickets by email.',
        'amount' => 'Amount to transfer',
        'deposit_then_balance' => 'Deposit · balance later',
        'bank_details' => 'Bank details',
        'reference_hint' => 'Enter the booking number as the :reference:',
        'reference_word' => 'payment reference',
        'go_to_booking' => 'Go to my booking',
        'transfer' => 'Bank transfer',
    ],

    'my_bookings' => [
        'title' => 'My bookings',
        'subtitle' => 'History, upcoming departures and tickets for your tours.',
        'empty_title' => 'No bookings yet',
        'empty_text' => 'Discover our catamaran tours along the coast. Your next experience starts here.',
        'today' => 'Today',
        'upcoming' => 'Upcoming',
        'user_fallback' => 'User',
    ],

    'detail' => [
        'booked_extras' => 'Booked extras',
        'passenger_todo' => '— Details to be completed —',
        'extra_fallback' => 'Extra',
        'tour_fallback' => 'Tour',
    ],

    'tickets' => [
        'title' => 'Your tickets',
        'one_per_passenger' => 'Each passenger has their own ticket.',
        'passenger' => 'Passenger',
        'adult' => 'Adult',
        'seat' => 'Seat',
        'catamaran' => 'Catamaran',
        'departure' => 'Departure',
        'boarding_point' => 'Boarding point',
        'boarded' => 'CHECKED IN',
        'tour' => 'Tour',
    ],

];
