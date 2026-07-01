<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Settings\ShopSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        /** @var Collection<int, string> $urls */
        $urls = collect(['home', 'about', 'services', 'projects', 'team', 'blog', 'contact', 'faq', 'testimonials', 'cgu', 'cgv', 'privacy', 'cookies'])
            ->map(fn (string $name): string => route($name));

        Service::query()->published()->pluck('slug')
            ->each(fn (string $slug) => $urls->push(route('services.show', $slug)));

        Project::query()->published()->pluck('slug')
            ->each(fn (string $slug) => $urls->push(route('projects.show', $slug)));

        Post::query()->published()->pluck('slug')
            ->each(fn (string $slug) => $urls->push(route('blog.show', $slug)));

        if (app(ShopSettings::class)->enabled) {
            $urls->push(route('shop'));
            Product::query()->active()->pluck('slug')
                ->each(fn (string $slug) => $urls->push(route('shop.show', $slug)));
        }

        $body = $urls
            ->map(fn (string $url): string => "  <url><loc>{$url}</loc></url>")
            ->implode("\n");

        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        {$body}
        </urlset>
        XML;

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
