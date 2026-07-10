<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class GuideController extends Controller
{
    /**
     * Indice dei capitoli della guida operativa.
     * 'slug' => [titolo, icona bootstrap, descrizione breve].
     *
     * Per aggiungere un capitolo: aggiungi una voce qui e crea la vista
     * resources/views/admin/guide/pages/{slug}.blade.php.
     */
    public const TOPICS = [
        'introduzione' => [
            'title' => 'Introduzione',
            'icon' => 'bi-book',
            'desc' => 'Panoramica del gestionale e come muoversi.',
        ],
        'prenotazioni' => [
            'title' => 'Creare una prenotazione',
            'icon' => 'bi-journal-plus',
            'desc' => 'Prenotazioni manuali, walk-in, telefoniche e retroattive.',
        ],
        'uso-esclusivo' => [
            'title' => 'Riservare un catamarano',
            'icon' => 'bi-water',
            'desc' => 'Uso esclusivo: periodo, orari e più catamarani.',
        ],
        'catamarani-posti' => [
            'title' => 'Catamarani e posti',
            'icon' => 'bi-people',
            'desc' => 'Come vengono assegnati i posti e gestita la capienza.',
        ],
        'pagamenti-stati' => [
            'title' => 'Pagamenti e stati',
            'icon' => 'bi-credit-card',
            'desc' => 'Stati della prenotazione, acconto, bonifico, link Stripe.',
        ],
        'agenzie-b2b' => [
            'title' => 'Agenzie B2B e commissioni',
            'icon' => 'bi-briefcase',
            'desc' => 'Portale agenzie, provvigioni, referral e liquidazione mensile.',
        ],
        'report' => [
            'title' => 'Report e statistiche',
            'icon' => 'bi-bar-chart',
            'desc' => 'Cosa mostra ogni scheda e voce: ricavi, prenotazioni, occupazione.',
        ],
        'documenti' => [
            'title' => "Documenti d'identità",
            'icon' => 'bi-person-vcard',
            'desc' => 'Obbligo documento per ogni passeggero, scadenza, modifica.',
        ],
        'impostazioni' => [
            'title' => 'Impostazioni',
            'icon' => 'bi-gear',
            'desc' => 'Acconto, bonifico e opzioni che cambiano il comportamento.',
        ],
    ];

    public function index(): View
    {
        return view('admin.guide.index', ['topics' => self::TOPICS]);
    }

    public function show(string $topic): View|RedirectResponse
    {
        if (! array_key_exists($topic, self::TOPICS)) {
            return redirect()->route('admin.guide.index');
        }

        return view('admin.guide.show', [
            'topics' => self::TOPICS,
            'current' => $topic,
            'meta' => self::TOPICS[$topic],
        ]);
    }
}
