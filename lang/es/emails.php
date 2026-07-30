<?php

/*
| Vedi lang/it/emails.php per la struttura e le note.
| I nomi dei :placeholder devono restare identici in ogni lingua.
*/

return [

    'common' => [
        'greeting' => 'Hola :name:',
        'booking' => 'Reserva',
        'tour' => 'Excursión',
        'date_departure' => 'Fecha y salida',
        'date' => 'Fecha',
        'participants' => 'Participantes',
        'passengers' => 'Pasajeros',
        'total' => 'Total',
        'questions' => 'Si tienes alguna duda, responde a este correo.',
        'rights' => 'Todos los derechos reservados',
        'footer_help' => '¿Tienes dudas? Responde a este correo o contacta con nuestro equipo.',
    ],

    'payment_link' => [
        'subject' => 'Completa el pago de tu reserva n.º :number',
        'title' => 'Confirma y paga tu reserva',
        'intro' => '¡gracias por reservar con nosotros! Para finalizar tu excursión :tour, completa el pago online de forma segura a través de Stripe.',
        'total_due' => 'Total a pagar',
        'cta' => 'Pagar ahora con tarjeta',
        'fallback' => 'Si el botón no funciona, copia y pega este enlace en el navegador:',
        'deadline' => 'Completa el pago antes del :date; de lo contrario la reserva caducará y las plazas volverán a estar disponibles.',
        'after_payment' => 'Una vez completado el pago, recibirás un segundo correo con tus billetes y los códigos QR que deberás mostrar en el embarque (uno por pasajero).',
        'important' => 'Importante:',
        'link_expires' => 'El enlace de pago caduca el :date.',
    ],

    'awaiting_transfer' => [
        'subject' => 'Instrucciones para la transferencia · Reserva n.º :number',
        'title' => 'Completa el pago por transferencia',
        'intro' => 'hemos registrado tu reserva n.º :number para :tour. Para confirmarla, realiza una transferencia con los datos siguientes.',
        'amount' => 'Importe a transferir',
        'bank_details' => 'Datos bancarios',
        'reference' => 'Concepto: :number',
        'after' => 'En cuanto recibamos la transferencia, confirmaremos la reserva y te enviaremos los billetes por correo electrónico.',
        'hint' => 'Indica siempre el número de reserva en el concepto para agilizar la comprobación.',
    ],

    'balance_reminder' => [
        'subject' => 'Paga el resto de tu reserva n.º :number',
        'title' => 'Completa el pago del importe restante',
        'intro' => 'se acerca la fecha de tu excursión :tour. Para completar la reserva n.º :number, te recordamos que debes abonar el importe restante.',
        'deposit_paid' => 'Anticipo abonado',
        'balance_due' => 'Resto a pagar',
        'deadline' => '⏰ Paga antes del :date.',
        'cta' => 'Pagar el resto',
    ],

    'tickets' => [
        'subject' => 'Tus billetes · Reserva n.º :number',
        'title' => '¡Pago confirmado! Aquí tienes tus billetes',
        'intro' => 'el pago se ha realizado correctamente. Adjunto a este correo encontrarás el PDF con los billetes de todos los pasajeros.',
        'instructions' => 'Imprímelo o muéstralo desde el móvil en el momento del embarque: cada billete tiene un código QR que se escaneará para registrar la asistencia.',
        'attachment' => '📎 Adjunto: biglietti-:number.pdf',
        'tip_label' => 'Consejo:',
        'tip' => 'preséntate en el muelle al menos 15 minutos antes de la salida con el PDF de los billetes (impreso o en el móvil).',
    ],

    'reminder_48h' => [
        'subject' => 'Excursión en 2 días — Reserva :number',
        'eyebrow' => 'Recordatorio · 48 horas',
        'title' => '¡Tu excursión está cerca!',
        'intro_with_time' => 'tu excursión :tour está prevista para el :date a las :time.',
        'intro_without_time' => 'tu excursión :tour está prevista para el :date.',
        'instructions' => 'Te recordamos que debes presentarte en el muelle al menos 15 minutos antes de la salida con el PDF de los billetes (adjunto al correo de confirmación), impreso o en el móvil. Se escaneará el QR de cada billete en el momento del embarque.',
        'closing' => '¡Buen viaje!',
    ],

    'reminder_24h' => [
        'subject' => '¡Mañana zarpas! Recordatorio de check-in - :number',
        'eyebrow' => 'Mañana se zarpa',
        'title' => '🌊 ¿Todo listo para tu excursión?',
        'intro' => '¡mañana zarpas! Aquí tienes un resumen de tu reserva y de los participantes registrados.',
        'registered' => 'Participantes registrados (:count)',
        'lead_booker' => 'Titular de la reserva',
        'tax_code' => 'NIF',
    ],

    'cancelled' => [
        'subject' => 'Reserva anulada · n.º :number',
        'title' => 'Reserva anulada',
        'intro' => 'te confirmamos que tu reserva n.º :number ha sido anulada.',
        'cancelled_at' => 'Anulada el',
        'reason' => 'Motivo',
        'refund_detail' => 'Detalle del reembolso',
        'amount_paid' => 'Importe abonado',
        'refund' => 'Reembolso (:percentage %)',
        'penalty' => 'Penalización retenida',
    ],

    'refunded' => [
        'subject' => 'Reembolso realizado · Reserva n.º :number',
        'title' => 'Reembolso realizado',
        'intro' => 'te confirmamos que se ha efectuado el reembolso correspondiente a la reserva n.º :number.',
        'timing' => 'El abono en la tarjeta utilizada para el pago puede tardar entre 5 y 10 días laborables, según la entidad.',
        'amount' => 'Importe reembolsado',
        'penalty' => 'Penalización por cancelación',
        'booked_date' => 'Fecha reservada',
        'note' => 'Nota',
        'closing' => 'Si tienes alguna duda sobre el abono o los plazos, responde a este correo.',
    ],

    'welcome' => [
        'subject' => 'Bienvenido a :app',
    ],

];
