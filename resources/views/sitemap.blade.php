{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach($pages as $route => $priority)
@foreach(['en', 'ar'] as $lang)
    <url>
        <loc>{{ route($route) }}?lang={{ $lang }}</loc>
        <xhtml:link rel="alternate" hreflang="ar" href="{{ route($route) }}?lang=ar" />
        <xhtml:link rel="alternate" hreflang="en" href="{{ route($route) }}?lang=en" />
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ route($route) }}" />
        <changefreq>{{ $route === 'landing' ? 'weekly' : 'monthly' }}</changefreq>
        <priority>{{ $priority }}</priority>
    </url>
@endforeach
@endforeach
@foreach($posts as $slug)
@foreach(['en', 'ar'] as $lang)
    <url>
        <loc>{{ route('blog.show', $slug) }}?lang={{ $lang }}</loc>
        <xhtml:link rel="alternate" hreflang="ar" href="{{ route('blog.show', $slug) }}?lang=ar" />
        <xhtml:link rel="alternate" hreflang="en" href="{{ route('blog.show', $slug) }}?lang=en" />
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
@endforeach
@endforeach
</urlset>
