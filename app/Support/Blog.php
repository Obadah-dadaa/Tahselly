<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Blog articles are Markdown files in resources/content/blog/{slug}.{ar|en}.md with a small front matter block:
 *
 *   ---
 *   title: ...
 *   description: ...   (search snippet, ~150 characters)
 *   date: 2026-10-30
 *   ---
 *
 * Both languages share the slug, so each article has one URL with ?lang=ar / ?lang=en.
 */
class Blog
{
    public static function path(): string
    {
        return resource_path('content/blog');
    }

    /**
     * @return Collection<int, array{slug: string, title: string, description: string, date: string, minutes: int, html: string}>
     */
    public static function all(string $locale): Collection
    {
        $files = glob(self::path().'/*.'.$locale.'.md') ?: [];
        $key = 'blog:'.$locale.':'.md5(implode('|', array_map(fn ($f) => $f.filemtime($f), $files)));

        // Cached as a plain array: the cache store refuses to unserialize objects
        return collect(Cache::remember($key, now()->addDay(), fn () => collect($files)
            ->map(fn (string $file) => self::parse($file, basename($file, '.'.$locale.'.md')))
            ->sortByDesc('date')
            ->values()
            ->all()));
    }

    public static function find(string $slug, string $locale): ?array
    {
        return self::all($locale)->firstWhere('slug', $slug);
    }

    /**
     * @return array{slug: string, title: string, description: string, date: string, minutes: int, html: string}
     */
    private static function parse(string $file, string $slug): array
    {
        $raw = str_replace("\r\n", "\n", file_get_contents($file));
        $meta = [];
        if (preg_match('/\A---\n(.*?)\n---\n/s', $raw, $m)) {
            foreach (explode("\n", $m[1]) as $line) {
                [$k, $v] = array_map('trim', explode(':', $line, 2)) + [1 => ''];
                $meta[$k] = $v;
            }
            $raw = substr($raw, strlen($m[0]));
        }
        $html = (new GithubFlavoredMarkdownConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]))->convert($raw)->getContent();

        return [
            'slug' => $slug,
            'title' => $meta['title'] ?? Str::headline($slug),
            'description' => $meta['description'] ?? Str::limit(strip_tags($html), 155),
            'date' => $meta['date'] ?? date('Y-m-d', filemtime($file)),
            'minutes' => max(2, (int) round(str_word_count(strip_tags($html), 0, 'ابتثجحخدذرزسشصضطظعغفقكلمنهويءآأؤإئةى') / 200)),
            'html' => $html,
        ];
    }
}
