@extends('layouts.admin')

@section('title', 'Notifiche')

@section('content')
    <div class="dash-page-header">
        <div>
            <h1>Notifiche</h1>
            <p>
                Cosa sta succedendo: nuove prenotazioni, incassi, scadenze e annullamenti.
                @if($nonLette > 0)
                    <strong>{{ $nonLette }}</strong> non {{ $nonLette === 1 ? 'letta' : 'lette' }}.
                @endif
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($notifiche->total() > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-light rounded-pill px-3 border fw-semibold">
                        <i class="bi bi-check2-all me-2"></i>Segna tutte lette
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.notifications.destroy-all') }}"
                      onsubmit="return confirm('Eliminare tutte le notifiche? Vale solo per te: gli altri operatori continueranno a vederle.');">
                    @csrf
                    <button type="submit" class="btn btn-light rounded-pill px-3 border fw-semibold text-danger">
                        <i class="bi bi-trash me-2"></i>Elimina tutte
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h3>
                <i class="bi bi-bell me-2 text-primary"></i>
                Elenco
                <span class="ms-2 badge bg-light text-secondary fw-medium">{{ $notifiche->total() }}</span>
            </h3>
            {{-- Letta/eliminata sono PER OPERATORE: va detto, altrimenti si
                 teme di nascondere le notifiche ai colleghi. --}}
            <div class="small text-muted">
                <i class="bi bi-info-circle me-1"></i>Letto ed eliminato valgono solo per te
            </div>
        </div>

        <div class="list-group list-group-flush">
            @forelse($notifiche as $n)
                @php $letta = $n->my_read_at !== null; @endphp
                <div class="list-group-item d-flex gap-3 align-items-start {{ $letta ? '' : 'bg-light' }}">
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0
                                 bg-{{ $n->color() }}-subtle text-{{ $n->color() }}"
                          style="width:40px;height:40px">
                        <i class="bi {{ $n->icon() }}"></i>
                    </span>

                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong class="text-dark">{{ $n->title }}</strong>
                            @unless($letta)
                                <span class="badge rounded-pill bg-primary" style="font-size:.65rem">nuova</span>
                            @endunless
                            <span class="badge rounded-pill bg-light text-secondary border" style="font-size:.65rem">
                                {{ $n->meta()['label'] }}
                            </span>
                        </div>
                        @if($n->body)
                            <div class="small text-muted">{{ $n->body }}</div>
                        @endif
                        <div class="small text-muted mt-1" style="font-size:.75rem">
                            {{ $n->created_at->locale('it')->isoFormat('D MMM YYYY · HH:mm') }}
                            @if($n->author) · da {{ $n->author->name }} @endif
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                        @if($n->booking_id)
                            <a href="{{ route('admin.notifications.read', $n) }}"
                               class="dash-icon-btn is-primary" title="Apri la prenotazione" data-bs-toggle="tooltip">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        @endif
                        @unless($letta)
                            <form method="POST" action="{{ route('admin.notifications.read-ajax', $n) }}">
                                @csrf
                                <button type="submit" class="dash-icon-btn is-success" title="Segna come letta" data-bs-toggle="tooltip">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('admin.notifications.destroy', $n) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dash-icon-btn is-danger" title="Elimina (solo per te)" data-bs-toggle="tooltip">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center py-5">
                    <i class="bi bi-bell-slash fs-1 text-muted d-block mb-2"></i>
                    <h2 class="h6 fw-bold">Nessuna notifica</h2>
                    <p class="text-muted small mb-0">Qui compariranno prenotazioni, incassi e scadenze.</p>
                </div>
            @endforelse
        </div>

        @if($notifiche->hasPages())
            <div class="px-3 py-3 border-top">
                {{ $notifiche->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
@endpush
