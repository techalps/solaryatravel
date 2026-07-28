<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use Illuminate\Http\Response;

/**
 * Sitemap XML bilingue.
 *
 * Ogni URL è dichiarato una volta per lingua, con annotazioni xhtml:link
 * reciproche (incluso x-default) come richiesto da Google per i siti
 * multilingua.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $locales = (array) config('locales.supported', ['it']);
        $xDefault = (string) config('locales.x_default', 'en');

        $tours = Tour::active()->ordered()->get(['slug', 'updated_at']);

        /** @var array<int, array{route: string, params: array<string, mixed>, priority: string, changefreq: string, lastmod: ?string}> $pages */
        $pages = [
            ['route' => 'home', 'params' => [], 'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => null],
            ['route' => 'tours.index', 'params' => [], 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => null],
            ['route' => 'booking.start', 'params' => [], 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => null],
        ];

        foreach ($tours as $tour) {
            $pages[] = [
                'route' => 'tours.show',
                'params' => ['slug' => $tour->slug],
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => $tour->updated_at?->toAtomString(),
            ];
        }

        foreach (['privacy', 'terms', 'cookies'] as $legal) {
            $pages[] = ['route' => $legal, 'params' => [], 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => null];
        }

        $xml = view('sitemap', compact('pages', 'locales', 'xDefault'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
