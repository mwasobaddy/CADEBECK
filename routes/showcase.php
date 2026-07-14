<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShowcaseController;

Route::get('/sitemap.xml', function () {
    $pages = [
        ['loc' => url('/'), 'priority' => '1.0'],
        ['loc' => url('/features'), 'priority' => '0.9'],
        ['loc' => url('/pricing'), 'priority' => '0.9'],
        ['loc' => url('/about'), 'priority' => '0.8'],
        ['loc' => url('/contact'), 'priority' => '0.8'],
        ['loc' => url('/resources'), 'priority' => '0.7'],
        ['loc' => url('/download-demo'), 'priority' => '0.7'],
    ];

    return response()->view('showcase.sitemap', compact('pages'))->header('Content-Type', 'text/xml');
});
