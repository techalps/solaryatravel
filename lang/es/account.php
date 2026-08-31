<?php

/*
| Vedi lang/it/account.php per la struttura e le note.
| I nomi dei :placeholder devono restare identici in ogni lingua.
*/

return [

    'common' => [
        'booking' => 'Reserva',
        'home' => 'Inicio',
        'your_tour' => 'Tu excursión',
        'summary' => 'Resumen',
        'order_summary' => 'Resumen del pedido',
        'amounts_summary' => 'Resumen de importes',
        'subtotal' => 'Subtotal',
        'extras' => 'Extras',
        'discount' => 'Descuento',
        'vat' => 'IVA',
        'vat_included' => 'IVA incluido',
        'total' => 'Total',
        'total_colon' => 'Total:',
        'status' => 'Estado',
        'duration' => 'Duración',
        'date' => 'Fecha',
        'method' => 'Método',
        'payment' => 'Pago',
        'lead_booker' => 'Titular',
        'name' => 'Nombre',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'special_requests' => 'Peticiones especiales',
        'participants' => 'Participantes',
        'person' => ':count persona|:count personas',
        'participant' => ':count participante|:count participantes',
        'seat' => ':count plaza|:count plazas',
        'hours' => ':count horas',
        'hours_short' => ':count h',
        'adult' => 'Adulto',
        'participants_count' => 'Participantes',
        'deadline' => 'Vencimiento',
        'actions' => 'Acciones',
        'cancelled_item' => 'Cancelado',
        'boarded' => 'Embarcado',
    ],

    'status_labels' => [
        'pending' => 'Pendiente de pago',
        'deposit_paid' => 'Anticipo abonado',
        'awaiting_transfer' => 'Pendiente de transferencia',
        'confirmed' => 'Confirmada',
        'checked_in' => 'Registrado',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
        'refunded' => 'Reembolsada',
        'no_show' => 'No presentado',
    ],

    'pay' => [
        'title' => 'Confirmar y pagar',
        'awaiting' => 'pendiente de pago',
        'lead_booker_title' => 'Titular de la reserva',
        'extras_included' => 'Extras incluidos:',
        'seats_held_for' => 'Plazas reservadas durante',
        'secure_payment' => 'Pago seguro',
        'stripe_note' => 'Pago gestionado por Stripe: tus datos no pasan por nuestro servidor.',
        'instant_confirm' => 'Confirmación inmediata y recibo enviado por correo electrónico.',
        'tickets_after' => 'Billetes con código QR entregados justo después del pago.',
    ],

    'success' => [
        'title' => '¡Reserva confirmada!',
        'intro' => ':name, hemos recibido tu pago.',
        'deposit_title' => 'Anticipo recibido',
        'deposit_intro' => '¡Gracias! Hemos registrado tu anticipo. Tu plaza está confirmada.',
        'deposit_tickets_later' => 'Recibirás los billetes por correo electrónico una vez abonado el resto.',
        'by_date' => 'antes del :date',
        'pay_balance' => 'Pagar el importe restante',
        'sent_to' => 'Hemos enviado los billetes y el recibo a',
        'all_tickets' => 'Billetes de todos los participantes',
        'total_paid' => 'Total pagado',
        'back_home' => 'Volver al inicio',
        'explore_tours' => 'Descubrir otras excursiones',
    ],

    'cancel' => [
        'title' => 'Pago cancelado',
        'intro' => 'Tu reserva sigue pendiente: puedes volver a intentarlo cuando quieras.',
        'no_charge' => 'No se ha cobrado ningún importe. La reserva sigue siendo válida hasta que caduque.',
        'total_due' => 'Total a pagar:',
    ],

    'balance' => [
        'title' => 'Pago del importe restante',
        'booking_total' => 'Total de la reserva',
        'deposit_paid' => 'Anticipo abonado',
        'balance' => 'Importe restante',
        'balance_due' => 'Importe pendiente',
        'secure_stripe' => 'Pago seguro a través de Stripe',
        'complete_payment' => 'Completar el pago',
    ],

    'transfer' => [
        'registered' => 'Reserva registrada',
        'title' => 'Completa el pago mediante transferencia inmediata',
        'intro' => 'La reserva :number está pendiente de pago y las plazas están reservadas. En cuanto recibamos la transferencia la confirmaremos y recibirás los billetes por correo electrónico.',
        'amount' => 'Importe a transferir',
        'deposit_then_balance' => 'Anticipo · resto más adelante',
        'bank_details' => 'Datos bancarios',
        'reference_hint' => 'Indica el número de reserva como :reference:',
        'reference_word' => 'concepto',
        'go_to_booking' => 'Ir a mi reserva',
        'transfer' => 'Transferencia',
    ],

    'my_bookings' => [
        'title' => 'Mis reservas',
        'subtitle' => 'Historial, próximas salidas y billetes de tus excursiones.',
        'empty_title' => 'Todavía no hay reservas',
        'empty_text' => 'Descubre nuestras excursiones en catamarán por la costa. Tu próxima experiencia empieza aquí.',
        'today' => 'Hoy',
        'upcoming' => 'Próximamente',
        'user_fallback' => 'Usuario',
    ],

    'detail' => [
        'booked_extras' => 'Extras reservados',
        'passenger_todo' => '— Datos por completar —',
        'extra_fallback' => 'Extra',
        'tour_fallback' => 'Excursión',
    ],

    'tickets' => [
        'title' => 'Tus billetes',
        'one_per_passenger' => 'Cada pasajero tiene su propio billete.',
        'passenger' => 'Pasajero',
        'adult' => 'Adulto',
        'seat' => 'Plaza',
        'catamaran' => 'Catamarán',
        'departure' => 'Salida',
        'boarding_point' => 'Punto de embarque',
        'boarded' => 'EMBARCADO',
        'tour' => 'Excursión',
    ],

];
