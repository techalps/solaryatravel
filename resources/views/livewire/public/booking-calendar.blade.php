<div class="bg-white rounded-4 border overflow-d-none">
 {{-- Calendar Header --}}
 <div class="d-flex align-items-center justify-content-between px-4 py-3 bg-light border-bottom">
 <button 
 wire:click="previousMonth"
 class="p-2 rounded-3 disabled:opacity-50 disabled:cursor-not-allowed"
 @disabled(\Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->lte(now()->startOfMonth()))
 >
 <svg class="text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
 </svg>
 </button>
 
 <h3 class="fs-5 fw-semibold text-navy text-none">{{ $monthName }}</h3>
 
 <button 
 wire:click="nextMonth"
 class="p-2 rounded-3 disabled:opacity-50 disabled:cursor-not-allowed"
 @disabled(\Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->gte(now()->addMonths(6)->startOfMonth()))
 >
 <svg class="text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
 </svg>
 </button>
 </div>
 
 {{-- Week Days Header --}}
 <div class="row gap-px bg-light">
 @foreach($weekDays as $day)
 <div class="bg-light px-2 py-2 text-center">
 <span class="small fw-medium text-muted text-uppercase">{{ $day }}</span>
 </div>
 @endforeach
 </div>
 
 {{-- Calendar Days --}}
 <div class="row gap-px bg-light">
 @foreach($days as $day)
 <div @class([
 'bg-white',
 'opacity-40' => !$day['isCurrentMonth'],
 ])>
 @if($day['isCurrentMonth'])
 @if($day['isAvailable'] && !$day['isPast'])
 <button 
 wire:click="selectDate('{{ $day['date'] }}')"
 @class([
 'w-100 h-100 d-flex align-items-center justify-content-center small fw-medium position-relative',
 'bg-warning text-dark' => $day['isSelected'],
 'bg-primary-subtle text-primary' => !$day['isSelected'] && $day['isAvailable'],
 ])
 >
 {{ $day['day'] }}
 @if($day['isToday'])
 <span class="position-absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 rounded-pill bg-current"></span>
 @endif
 </button>
 @else
 <div @class([
 'w-100 h-100 d-flex align-items-center justify-content-center small fw-medium',
 'text-muted' => $day['isPast'] || !$day['isAvailable'],
 'bg-light' => !$day['isAvailable'] && !$day['isPast'],
 ])>
 {{ $day['day'] }}
 @if($day['isToday'])
 <span class="position-absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 rounded-pill bg-primary-subtle0"></span>
 @endif
 </div>
 @endif
 @else
 <div class="w-100 $1 d-flex align-items-center justify-content-center small text-muted">
 {{ $day['day'] }}
 </div>
 @endif
 </div>
 @endforeach
 </div>
 
 {{-- Legend --}}
 <div class="d-flex align-items-center justify-content-center g-3 px-4 py-3 border-top bg-light">
 <div class="d-flex align-items-center g-3 small text-secondary">
 <span class="rounded bg-primary-subtle border border-primary"></span>
 <span>Disponibile</span>
 </div>
 <div class="d-flex align-items-center g-3 small text-secondary">
 <span class="rounded bg-warning-subtle0"></span>
 <span>Selezionato</span>
 </div>
 <div class="d-flex align-items-center g-3 small text-secondary">
 <span class="rounded bg-light border"></span>
 <span>Non disponibile</span>
 </div>
 </div>
</div>
