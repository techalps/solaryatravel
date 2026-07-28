<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach($pages as $page)
@foreach($locales as $locale)
    <url>
        <loc>{{ locale_route($page['route'], $page['params'], $locale) }}</loc>
@foreach($locales as $alternate)
        <xhtml:link rel="alternate" hreflang="{{ $alternate }}" href="{{ locale_route($page['route'], $page['params'], $alternate) }}"/>
@endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ locale_route($page['route'], $page['params'], $xDefault) }}"/>
@if($page['lastmod'])
        <lastmod>{{ $page['lastmod'] }}</lastmod>
@endif
        <changefreq>{{ $page['changefreq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
@endforeach
@endforeach
</urlset>
