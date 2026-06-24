<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Tour;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Genera un singolo file Excel (.xlsx) con un foglio per ogni report:
 * Riepilogo, Ricavi (giornaliero + per tour), Prenotazioni (dettaglio),
 * Occupazione. I criteri rispecchiano esattamente quelli di ReportController:
 * ricavo/occupazione per DATA ESCURSIONE (booking_date), conteggi prenotazioni
 * per DATA CREAZIONE (created_at). Venduto = total_amount, Incassato = amount_paid.
 */
class ReportExportService
{
    private const HEADER_FILL = 'FF0F766E';   // teal scuro
    private const MONEY_FMT = '#,##0.00 "€"';

    public function build(Carbon $startDate, Carbon $endDate, string $periodLabel): Spreadsheet
    {
        $revenueStatuses = BookingStatus::revenueStatusValues();

        $book = new Spreadsheet();
        $book->getProperties()
            ->setTitle('Report Solarya Travel')
            ->setCreator('Solarya Travel');

        $this->sheetRiepilogo($book->getActiveSheet(), $startDate, $endDate, $periodLabel, $revenueStatuses);
        $this->sheetRicaviGiornalieri($book->createSheet(), $startDate, $endDate, $revenueStatuses);
        $this->sheetRicaviPerTour($book->createSheet(), $startDate, $endDate, $revenueStatuses);
        $this->sheetPrenotazioni($book->createSheet(), $startDate, $endDate);
        $this->sheetOccupazione($book->createSheet(), $startDate, $endDate);

        $book->setActiveSheetIndex(0);

        return $book;
    }

    /**
     * Scrive il workbook nello stream di output e restituisce i byte come stringa.
     */
    public function toString(Spreadsheet $book): string
    {
        $writer = new Xlsx($book);
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    public function filename(string $periodLabel): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($periodLabel));

        return 'report-solarya-' . trim($slug, '-') . '-' . now()->format('Y-m-d') . '.xlsx';
    }

    // ---------------------------------------------------------------- fogli

    private function sheetRiepilogo($sheet, Carbon $start, Carbon $end, string $periodLabel, array $statuses): void
    {
        $sheet->setTitle('Riepilogo');

        $venduto = (float) Booking::whereIn('status', $statuses)
            ->whereBetween('booking_date', [$start, $end])->sum('total_amount');
        $incassato = (float) Booking::whereIn('status', $statuses)
            ->whereBetween('booking_date', [$start, $end])->sum('amount_paid');
        $rimborsi = (float) Payment::where('status', PaymentStatus::REFUNDED)
            ->whereBetween('created_at', [$start, $end])->sum('amount');

        $prenotazioni = Booking::whereBetween('created_at', [$start, $end])->count();
        $confermate = Booking::where('status', BookingStatus::CONFIRMED)
            ->whereBetween('created_at', [$start, $end])->count();
        $annullate = Booking::where('status', BookingStatus::CANCELLED)
            ->whereBetween('created_at', [$start, $end])->count();
        $passeggeri = (int) Booking::whereBetween('created_at', [$start, $end])->sum('seats');

        $sheet->setCellValue('A1', 'Report Solarya Travel');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->setCellValue('A2', 'Periodo: ' . $periodLabel
            . ' (' . $start->format('d/m/Y') . ' → ' . $end->format('d/m/Y') . ')');
        $sheet->setCellValue('A3', 'Generato il ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A2:A3')->getFont()->getColor()->setRGB('64748B');

        $rows = [
            ['Voce', 'Valore'],
            ['Venduto (totale prenotazioni)', $venduto],
            ['Incassato (versato finora)', $incassato],
            ['Da incassare (saldi mancanti)', max(0, $venduto - $incassato)],
            ['Rimborsi erogati', $rimborsi],
            ['', ''],
            ['Prenotazioni create', $prenotazioni],
            ['di cui confermate', $confermate],
            ['di cui annullate', $annullate],
            ['Passeggeri (posti venduti)', $passeggeri],
        ];

        $r = 5;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$r}", $row[0]);
            $sheet->setCellValue("B{$r}", $row[1]);
            $r++;
        }

        $this->styleHeader($sheet, "A5:B5");
        // Formato valuta sulle 4 righe monetarie (6..9).
        $sheet->getStyle('B6:B9')->getNumberFormat()->setFormatCode(self::MONEY_FMT);
        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(20);
    }

    private function sheetRicaviGiornalieri($sheet, Carbon $start, Carbon $end, array $statuses): void
    {
        $sheet->setTitle('Ricavi giornalieri');

        $daily = Booking::whereIn('status', $statuses)
            ->whereBetween('booking_date', [$start, $end])
            ->selectRaw('DATE(booking_date) as date, SUM(total_amount) as venduto, SUM(amount_paid) as incassato, COUNT(*) as prenotazioni')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $headers = ['Data escursione', 'Prenotazioni', 'Venduto', 'Incassato', 'Media venduto'];
        $this->writeHeaderRow($sheet, $headers);

        $r = 2;
        foreach ($daily as $d) {
            $venduto = (float) $d->venduto;
            $n = max(1, (int) $d->prenotazioni);
            $sheet->setCellValue("A{$r}", Carbon::parse($d->date)->format('d/m/Y'));
            $sheet->setCellValue("B{$r}", (int) $d->prenotazioni);
            $sheet->setCellValue("C{$r}", $venduto);
            $sheet->setCellValue("D{$r}", (float) $d->incassato);
            $sheet->setCellValue("E{$r}", $venduto / $n);
            $r++;
        }

        $sheet->getStyle("C2:E{$r}")->getNumberFormat()->setFormatCode(self::MONEY_FMT);
        $this->autosize($sheet, ['A' => 16, 'B' => 13, 'C' => 14, 'D' => 14, 'E' => 14]);
    }

    private function sheetRicaviPerTour($sheet, Carbon $start, Carbon $end, array $statuses): void
    {
        $sheet->setTitle('Ricavi per tour');

        $perTour = Booking::with('tour')
            ->whereIn('status', $statuses)
            ->whereBetween('booking_date', [$start, $end])
            ->selectRaw('tour_id, SUM(total_amount) as venduto, SUM(amount_paid) as incassato, COUNT(*) as prenotazioni, SUM(seats) as passeggeri')
            ->groupBy('tour_id')
            ->orderByDesc('venduto')
            ->get();

        $headers = ['Tour', 'Prenotazioni', 'Passeggeri', 'Venduto', 'Incassato'];
        $this->writeHeaderRow($sheet, $headers);

        $r = 2;
        foreach ($perTour as $t) {
            $sheet->setCellValue("A{$r}", $t->tour->name ?? 'Tour sconosciuto');
            $sheet->setCellValue("B{$r}", (int) $t->prenotazioni);
            $sheet->setCellValue("C{$r}", (int) $t->passeggeri);
            $sheet->setCellValue("D{$r}", (float) $t->venduto);
            $sheet->setCellValue("E{$r}", (float) $t->incassato);
            $r++;
        }

        $sheet->getStyle("D2:E{$r}")->getNumberFormat()->setFormatCode(self::MONEY_FMT);
        $this->autosize($sheet, ['A' => 34, 'B' => 13, 'C' => 13, 'D' => 14, 'E' => 14]);
    }

    private function sheetPrenotazioni($sheet, Carbon $start, Carbon $end): void
    {
        $sheet->setTitle('Prenotazioni');

        $bookings = Booking::with(['tour', 'departure'])
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->get();

        $headers = [
            'Numero', 'Creata il', 'Data escursione', 'Orario', 'Tour', 'Cliente', 'Email',
            'Posti', 'Stato', 'Pagamento', 'Venduto', 'Incassato', 'Saldo residuo',
        ];
        $this->writeHeaderRow($sheet, $headers);

        $r = 2;
        foreach ($bookings as $b) {
            $time = $b->departure?->start_time;
            $venduto = (float) $b->total_amount;
            $incassato = (float) $b->amount_paid;
            $sheet->setCellValueExplicit("A{$r}", (string) $b->booking_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("B{$r}", $b->created_at->format('d/m/Y H:i'));
            $sheet->setCellValue("C{$r}", $b->booking_date?->format('d/m/Y') ?? '-');
            $sheet->setCellValue("D{$r}", $time ? Carbon::parse($time)->format('H:i') : '-');
            $sheet->setCellValue("E{$r}", $b->tour->name ?? '-');
            $sheet->setCellValue("F{$r}", $b->customer_full_name);
            $sheet->setCellValue("G{$r}", $b->customer_email);
            $sheet->setCellValue("H{$r}", (int) $b->seats);
            $sheet->setCellValue("I{$r}", $b->status->label());
            $sheet->setCellValue("J{$r}", $this->paymentTypeLabel($b->payment_type));
            $sheet->setCellValue("K{$r}", $venduto);
            $sheet->setCellValue("L{$r}", $incassato);
            $sheet->setCellValue("M{$r}", max(0, $venduto - $incassato));
            $r++;
        }

        $sheet->getStyle("K2:M{$r}")->getNumberFormat()->setFormatCode(self::MONEY_FMT);
        $this->autosize($sheet, [
            'A' => 14, 'B' => 16, 'C' => 15, 'D' => 8, 'E' => 26, 'F' => 22, 'G' => 26,
            'H' => 7, 'I' => 16, 'J' => 14, 'K' => 13, 'L' => 13, 'M' => 13,
        ]);
        $sheet->freezePane('A2');
    }

    private function sheetOccupazione($sheet, Carbon $start, Carbon $end): void
    {
        $sheet->setTitle('Occupazione');

        $statuses = [BookingStatus::CONFIRMED, BookingStatus::COMPLETED];
        $tours = Tour::where('is_active', true)->get();

        $headers = ['Tour', 'Capacità/slot', 'Partenze usate', 'Capacità max', 'Passeggeri', 'Occupazione %'];
        $this->writeHeaderRow($sheet, $headers);

        $r = 2;
        foreach ($tours as $tour) {
            $bookings = Booking::where('tour_id', $tour->id)
                ->whereIn('status', $statuses)
                ->whereBetween('booking_date', [$start, $end])
                ->get();

            $passeggeri = (int) $bookings->sum('seats');
            $partenze = $bookings->pluck('tour_departure_id')->filter()->unique()->count();
            $capSlot = (int) ($tour->max_capacity ?? 0);
            $capMax = $capSlot * max($partenze, 1);
            $occ = $capMax > 0 ? round(($passeggeri / $capMax) * 100, 1) : 0;

            if ($passeggeri === 0 && $partenze === 0) {
                continue; // niente attività nel periodo: salta per non sporcare il foglio
            }

            $sheet->setCellValue("A{$r}", $tour->name);
            $sheet->setCellValue("B{$r}", $capSlot);
            $sheet->setCellValue("C{$r}", $partenze);
            $sheet->setCellValue("D{$r}", $capMax);
            $sheet->setCellValue("E{$r}", $passeggeri);
            $sheet->setCellValue("F{$r}", $occ);
            $r++;
        }

        $sheet->getStyle("F2:F{$r}")->getNumberFormat()->setFormatCode('0.0"%"');
        $this->autosize($sheet, ['A' => 34, 'B' => 13, 'C' => 15, 'D' => 14, 'E' => 13, 'F' => 14]);
    }

    // ---------------------------------------------------------------- util

    private function paymentTypeLabel(?string $type): string
    {
        return match ($type) {
            'full' => 'Saldo intero',
            'deposit' => 'Acconto',
            'bank_transfer' => 'Bonifico',
            default => $type ?? '-',
        };
    }

    private function writeHeaderRow($sheet, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $col++;
        }
        $lastCol = chr(ord('A') + count($headers) - 1);
        $this->styleHeader($sheet, "A1:{$lastCol}1");
    }

    private function styleHeader($sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB(substr(self::HEADER_FILL, 2));
        $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function autosize($sheet, array $widths): void
    {
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
    }
}
