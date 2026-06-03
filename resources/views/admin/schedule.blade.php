@extends('layouts.admin')

@section('title', 'Programma')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
@endpush

@section('content')
    @php
        $statusLegend = [
            ['label' => 'In attesa',  'color' => '#f59e0b'],
            ['label' => 'Confermata', 'color' => '#10b981'],
            ['label' => 'Check-in',   'color' => '#0284c7'],
            ['label' => 'Completata', 'color' => '#3b82f6'],
            ['label' => 'No show',    'color' => '#6b7280'],
        ];
    @endphp

    {{-- Page header --}}
    <div class="dash-page-header">
        <div>
            <h1>Programma</h1>
            <p>Calendario delle prenotazioni di questo mese e del prossimo.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="position-relative">
                <i class="bi bi-water position-absolute top-50 start-0 translate-middle-y ms-3 text-primary"></i>
                <select id="catamaran-filter" class="form-select rounded-pill ps-5 pe-4 fw-medium" style="min-width:220px">
                    <option value="">Tutti i catamarani</option>
                    @foreach($catamarans as $catamaran)
                        <option value="{{ $catamaran->id }}">{{ $catamaran->name }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-light rounded-pill px-3 fw-semibold border">
                <i class="bi bi-list-ul me-2"></i>Lista
            </a>
        </div>
    </div>

    {{-- Mini stats --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <span class="mini-stat-icon bg-primary-subtle text-primary"><i class="bi bi-calendar-event"></i></span>
                <div>
                    <div class="mini-stat-value" id="stat-total">{{ count($bookings) }}</div>
                    <div class="mini-stat-label">Totali periodo</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <span class="mini-stat-icon bg-warning-subtle text-warning"><i class="bi bi-sun"></i></span>
                <div>
                    <div class="mini-stat-value" id="stat-today">0</div>
                    <div class="mini-stat-label">Oggi</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <span class="mini-stat-icon bg-info-subtle text-info"><i class="bi bi-calendar-week"></i></span>
                <div>
                    <div class="mini-stat-value" id="stat-week">0</div>
                    <div class="mini-stat-label">Questa settimana</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dash-mini-stat">
                <span class="mini-stat-icon bg-success-subtle text-success"><i class="bi bi-people-fill"></i></span>
                <div>
                    <div class="mini-stat-value" id="stat-guests">0</div>
                    <div class="mini-stat-label">Ospiti questo mese</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="dash-card mb-3">
        <div class="dash-card-body py-3">
            <div class="cal-legend">
                <span class="small fw-semibold text-uppercase text-muted" style="letter-spacing:.05em">
                    <i class="bi bi-palette me-1"></i>Legenda
                </span>
                @foreach($statusLegend as $l)
                    <span class="cal-legend-item">
                        <span class="cal-legend-dot" style="background: {{ $l['color'] }}"></span>
                        {{ $l['label'] }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Calendar --}}
    <div class="dash-card mb-4">
        <div class="dash-card-body">
            <div class="dash-calendar">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    {{-- Booking Details Modal (Bootstrap) --}}
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:1rem">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-calendar-check me-2 text-primary"></i>Dettagli prenotazione
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-content"></div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3 border" data-bs-dismiss="modal">Chiudi</button>
                    <a href="#" id="modal-link" class="btn btn-primary rounded-pill px-3 fw-semibold">
                        <i class="bi bi-arrow-right me-2"></i>Apri prenotazione
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const bookings = @json($bookings);

        // Compute mini stats
        const today = new Date(); today.setHours(0,0,0,0);
        const startOfWeek = new Date(today); startOfWeek.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1));
        const endOfWeek = new Date(startOfWeek); endOfWeek.setDate(startOfWeek.getDate() + 6); endOfWeek.setHours(23,59,59,999);
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0, 23, 59, 59);

        let cToday = 0, cWeek = 0, gMonth = 0;
        bookings.forEach(b => {
            const d = new Date(b.start);
            if (d >= today && d < new Date(today.getTime() + 86400000)) cToday++;
            if (d >= startOfWeek && d <= endOfWeek) cWeek++;
            if (d >= monthStart && d <= monthEnd) gMonth += (b.extendedProps.guests || 0);
        });
        document.getElementById('stat-today').textContent = cToday;
        document.getElementById('stat-week').textContent = cWeek;
        document.getElementById('stat-guests').textContent = gMonth;

        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'it',
            firstDay: 1,
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            buttonText: {
                today: 'Oggi',
                month: 'Mese',
                week: 'Settimana',
                day: 'Giorno',
                list: 'Lista'
            },
            dayMaxEvents: 3,
            moreLinkText: n => '+ ' + n + ' altre',
            events: bookings,
            eventClick: function (info) {
                showBookingDetails(info.event);
            },
            eventDidMount: function (info) {
                info.el.style.cursor = 'pointer';
                info.el.style.borderLeftColor = info.event.backgroundColor;
            }
        });
        calendar.render();

        // Filter by catamaran
        document.getElementById('catamaran-filter').addEventListener('change', function () {
            const catamaranId = this.value;
            calendar.getEvents().forEach(event => {
                if (catamaranId === '' || String(event.extendedProps.catamaran_id) === catamaranId) {
                    event.setProp('display', 'auto');
                } else {
                    event.setProp('display', 'none');
                }
            });
        });

        const modalEl = document.getElementById('bookingModal');
        const bsModal = new bootstrap.Modal(modalEl);

        const statusBadges = {
            pending:    { cls: 's-pending',    label: 'In attesa',  icon: 'bi-hourglass-split' },
            confirmed:  { cls: 's-confirmed',  label: 'Confermata', icon: 'bi-check-circle' },
            checked_in: { cls: 's-checked_in', label: 'Check-in',   icon: 'bi-qr-code-scan' },
            completed:  { cls: 's-completed',  label: 'Completata', icon: 'bi-flag-fill' },
            cancelled:  { cls: 's-cancelled',  label: 'Annullata',  icon: 'bi-x-circle' },
            no_show:    { cls: 's-no_show',    label: 'No show',    icon: 'bi-eye-slash' },
        };

        function row(icon, label, value) {
            return `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small"><i class="bi ${icon} me-2"></i>${label}</span>
                    <span class="fw-semibold text-end">${value}</span>
                </div>`;
        }

        function showBookingDetails(event) {
            const p = event.extendedProps;
            const s = statusBadges[p.status] || { cls: '', label: p.status, icon: 'bi-circle' };
            const dateStr = event.start.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            const startStr = event.start.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
            const endStr = event.end ? event.end.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' }) : 'N/A';

            document.getElementById('modal-content').innerHTML = `
                <div class="text-center mb-3">
                    <span class="status-pill ${s.cls}"><i class="bi ${s.icon}"></i>${s.label}</span>
                    <h4 class="mt-3 mb-1 fw-bold">${event.title}</h4>
                    <div class="text-muted small">#${p.booking_number}</div>
                </div>
                <div class="bg-light rounded-3 p-3">
                    ${row('bi-compass', 'Tour', p.tour || 'N/A')}
                    ${row('bi-calendar3', 'Data', dateStr)}
                    ${row('bi-clock', 'Orario', `${startStr} – ${endStr}`)}
                    ${row('bi-water', 'Catamarano', p.catamaran || '<span class="text-muted fw-normal">Non assegnato</span>')}
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="text-muted small"><i class="bi bi-people me-2"></i>Ospiti</span>
                        <span class="fw-semibold">${p.guests}</span>
                    </div>
                </div>
            `;
            document.getElementById('modal-link').href = `/admin/bookings/${event.id}`;
            bsModal.show();
        }
    });
</script>
@endpush
