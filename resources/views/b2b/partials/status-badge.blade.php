@php
    // Mappa il colore semantico dell'enum BookingStatus a una coppia Bootstrap.
    $map = [
        'yellow' => 'bg-warning-subtle text-warning-emphasis',
        'teal'   => 'bg-info-subtle text-info-emphasis',
        'orange' => 'bg-warning-subtle text-warning-emphasis',
        'green'  => 'bg-success-subtle text-success-emphasis',
        'blue'   => 'bg-primary-subtle text-primary-emphasis',
        'gray'   => 'bg-secondary-subtle text-secondary-emphasis',
        'red'    => 'bg-danger-subtle text-danger-emphasis',
        'purple' => 'bg-secondary-subtle text-secondary-emphasis',
    ];
    $cls = $map[$status->color()] ?? 'bg-light text-secondary';
@endphp
<span class="badge rounded-pill {{ $cls }} fw-medium">{{ $status->label() }}</span>
