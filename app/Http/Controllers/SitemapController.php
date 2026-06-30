<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Settings\ShopSettings;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $routes = ['home', 'services', 'achat', 'flights', 'track', 'about', 'team', 'contact', 'cgu', 'privacy', 'cookies'];

        $urls = collect($routes)
            ->map(fn (string $name): string => route($name))
            ->map(fn (string $url): string => "  <url><loc>{$url}</loc></url>");

        if (app(ShopSettings::class)->enabled) {
            $urls->push('  <url><loc>'.route('shop').'</loc></url>');
            Product::active()->pluck('slug')->each(function (string $slug) use ($urls): void {
                $urls->push('  <url><loc>'.route('shop.show', $slug).'</loc></url>');
            });
        }

        $urls = $urls->implode("\n");

        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        {$urls}
        </urlset>
        XML;

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
