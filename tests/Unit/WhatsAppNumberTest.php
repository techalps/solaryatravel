<?php

namespace Tests\Unit;

use App\Support\WhatsApp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Normalizzazione dei numeri per i link wa.me.
 *
 * `bookings.customer_phone` è un campo a testo libero: i clienti lo scrivono
 * con spazi, punti, prefisso "+", "0039" o senza prefisso del tutto. wa.me
 * accetta solo cifre col prefisso internazionale, quindi ogni variante deve
 * ricondursi allo stesso numero — o essere scartata se non è utilizzabile.
 */
class WhatsAppNumberTest extends TestCase
{
    #[DataProvider('validNumbers')]
    public function test_normalizza_i_numeri_validi(string $input, ?string $country, string $expected): void
    {
        $this->assertSame($expected, WhatsApp::normalizeNumber($input, $country));
    }

    public static function validNumbers(): array
    {
        return [
            // Già in formato internazionale, con separatori di ogni tipo.
            'con più' => ['+39 345 088 4743', 'IT', '393450884743'],
            'con punti' => ['+39.345.088.4743', 'IT', '393450884743'],
            'con trattini' => ['+39-345-088-4743', null, '393450884743'],
            'con prefisso 0039' => ['0039 345 088 4743', 'IT', '393450884743'],

            // Numero locale: il prefisso si deduce dal paese del cliente.
            'locale italiano' => ['345 088 4743', 'IT', '393450884743'],
            'locale senza paese' => ['3450884743', null, '393450884743'],
            'locale francese' => ['06 12 34 56 78', 'FR', '33612345678'],
            'locale tedesco' => ['0151 23456789', 'DE', '4915123456789'],
            'locale UK' => ['07700 900123', 'GB', '447700900123'],

            // Il paese non deve sovrascrivere un prefisso già esplicito:
            // un francese che lascia il suo numero resta francese.
            'prefisso esplicito vince sul paese' => ['+33 6 12 34 56 78', 'IT', '33612345678'],

            // Paese sconosciuto o non in tabella: fallback su +39.
            'paese non mappato' => ['3450884743', 'ZZ', '393450884743'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_scarta_i_numeri_inutilizzabili(?string $input, ?string $country): void
    {
        $this->assertNull(WhatsApp::normalizeNumber($input, $country));
    }

    public static function invalidNumbers(): array
    {
        return [
            'null' => [null, 'IT'],
            'stringa vuota' => ['', 'IT'],
            'solo spazi' => ['   ', 'IT'],
            'senza cifre' => ['non disponibile', 'IT'],
            'troppo corto' => ['12345', 'IT'],
            'solo zeri' => ['000', 'IT'],
            'troppo lungo' => ['+39 3450884743123456', 'IT'],

            // Numeri di prova rimasti nei dati: superano il minimo E.164 solo
            // grazie al prefisso aggiunto, ma la parte nazionale è incompleta.
            'parte nazionale di 6 cifre' => ['123456', 'IT'],
            'parte nazionale corta con prefisso lungo' => ['+351 12345', 'PT'],
        ];
    }

    #[DataProvider('realWorldNumbers')]
    public function test_accetta_numeri_di_lunghezza_reale(string $input, ?string $country, string $expected): void
    {
        $this->assertSame($expected, WhatsApp::normalizeNumber($input, $country));
    }

    public static function realWorldNumbers(): array
    {
        return [
            // Il controllo sulla parte nazionale non deve scartare numeri veri.
            'cellulare italiano 10 cifre' => ['3450884743', 'IT', '393450884743'],
            'fisso italiano con prefisso urbano' => ['0471 123456', 'IT', '39471123456'],
            'numero USA (prefisso di 1 cifra)' => ['+1 415 555 0123', 'US', '14155550123'],
            'numero portoghese' => ['+351 912 345 678', 'PT', '351912345678'],
            'numero ucraino (prefisso di 3 cifre)' => ['+380 67 123 4567', 'UA', '380671234567'],
            // Paese fuori tabella: si accetta se la lunghezza è plausibile.
            'prefisso non in tabella' => ['+675 7123 4567', null, '67571234567'],
        ];
    }

    public function test_il_link_include_il_messaggio_codificato(): void
    {
        $link = WhatsApp::link('393450884743', 'Ciao, prenotazione #SLY-1 del 12/08');

        // Gli spazi vanno in %20 (non in "+"): WhatsApp non decodifica il "+".
        $this->assertSame(
            'https://wa.me/393450884743?text=Ciao%2C%20prenotazione%20%23SLY-1%20del%2012%2F08',
            $link
        );
    }

    public function test_il_link_senza_messaggio_non_ha_query_string(): void
    {
        $this->assertSame('https://wa.me/393450884743', WhatsApp::link('393450884743'));
    }

    public function test_nessun_numero_nessun_link(): void
    {
        $this->assertNull(WhatsApp::link(null, 'messaggio'));
    }
}
