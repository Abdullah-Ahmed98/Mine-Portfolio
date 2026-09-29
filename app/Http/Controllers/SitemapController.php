<?php

namespace App\Http\Controllers;

use App\Services\PortfolioService;
use Illuminate\Http\Response;

/**
 * The site is one page, so the sitemap lists the home page and, when one is
 * uploaded, the resume download.
 */
class SitemapController extends Controller
{
    public function __invoke(PortfolioService $portfolio): Response
    {
        $urls = collect([route('home')]);

        if ($portfolio->profile()->hasCv()) {
            $urls->push(route('resume'));
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
