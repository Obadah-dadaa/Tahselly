<?php

namespace App\Http\Controllers;

use App\Support\Blog;
use Illuminate\View\View;

/**
 * Public articles about getting invoices paid, written for search: one URL per article, ?lang= picks the language.
 */
class BlogController extends Controller
{
    public function index(): View
    {
        return view('blog.index', ['posts' => Blog::all(app()->getLocale())]);
    }

    public function show(string $slug): View
    {
        $locale = app()->getLocale();
        $post = Blog::find($slug, $locale) ?? abort(404);

        return view('blog.show', [
            'post' => $post,
            'more' => Blog::all($locale)->where('slug', '!=', $slug)->take(3),
        ]);
    }
}
