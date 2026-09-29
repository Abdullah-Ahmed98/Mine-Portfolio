<?php

namespace App\Console\Commands;

use App\Http\Controllers\HomeController;
use App\Models\Profile;
use App\Services\PortfolioService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\Finder\Finder;

/**
 * Renders the public site to plain files, for hosting without a PHP runtime.
 *
 * The site is a single page plus a CV, so the whole thing can be frozen at build
 * time. This exists because a free dynamic host (Render, Railway, Koyeb) all ask
 * for a card, while static hosts do not.
 *
 * Two things are rewritten on the way out, because they are generated at runtime
 * and have no meaning in a static file:
 *
 * - route() URLs become absolute paths pointing at the exported copy, so the
 *   form still submits and the resume still downloads.
 * - The contact form cannot be handled by a file server at all, so it is pointed
 *   at Formspree and stripped of the CSRF field, which would be rejected.
 */
class ExportStaticSite extends Command
{
    /**
     * @var string
     */
    protected $signature = 'site:export
        {--output=public/static-build : Directory to write the site into}
        {--url= : Public base URL, e.g. https://example.com}
        {--clean : Empty the output directory first}';

    /**
     * @var string
     */
    protected $description = 'Render the public site into static HTML, CSS, JS and images';

    public function handle(): int
    {
        $output = base_path($this->option('output'));
        $baseUrl = $this->baseUrl();

        $this->useBaseUrl($baseUrl);

        $this->components->info("Exporting to {$output}");

        if ($this->option('clean') && is_dir($output)) {
            $this->deleteDirectory($output);
        }

        if (! is_dir($output) && ! mkdir($output, 0o755, true) && ! is_dir($output)) {
            $this->components->error("Could not create {$output}.");

            return self::FAILURE;
        }

        $this->comment('Rendering pages');

        $written = 0;
        $written += $this->write($output.'/index.html', $this->renderHome());
        $written += $this->write($output.'/404.html', $this->renderHome());
        $written += $this->write($output.'/robots.txt', $this->renderRobots($baseUrl));
        $written += $this->write($output.'/sitemap.xml', $this->renderSitemap($baseUrl));
        $written += $this->write($output.'/_headers', $this->headers());

        $this->comment('Copying the CV');
        $written += $this->copyCv($output);

        $this->comment('Copying images');
        $written += $this->copyMedia($output);

        $this->comment('Copying built assets');
        $written += $this->copyBuildAssets($output);

        $this->newLine();
        $this->components->info(sprintf('Wrote %d file(s) to %s', $written, $output));
        $this->newLine();
        $this->components->twoColumnDetail('Upload that folder to', 'Cloudflare Pages (or Netlify / GitHub Pages)');
        $this->components->twoColumnDetail('Contact form', $this->formspreeConfigured() ? 'pointed at Formspree' : 'NOT configured yet, see below');

        if (! $this->formspreeConfigured()) {
            $this->newLine();
            $this->components->warn('Set FORMSPREE_ENDPOINT in .env, then export again, or the form will not submit anywhere.');
        }

        return self::SUCCESS;
    }

    /**
     * The address the exported site will be served from.
     *
     * The --url option wins, because locally app.url points at the dev server and
     * baking that into a public build would ship every canonical link, OG tag and
     * JSON-LD id pointing at localhost.
     */
    private function baseUrl(): string
    {
        $url = $this->option('url') ?: config('app.url');

        return rtrim($url, '/');
    }

    /**
     * Point every URL helper at the export's address for the rest of the run.
     *
     * The page builds its URLs from three separate places, so all three are
     * overridden rather than the rendered HTML being patched afterwards:
     *
     * - app.url, which backs url() and therefore route() and asset()
     * - the public disk url, which is what Storage::url() returns for the images
     * - the URL generator's forced root, which would otherwise win over app.url
     *   when a request has already been bound
     */
    private function useBaseUrl(string $baseUrl): void
    {
        config([
            'app.url' => $baseUrl,
            'filesystems.disks.public.url' => $baseUrl.'/storage',
        ]);

        URL::forceRootUrl($baseUrl);
        URL::forceScheme(Str::startsWith($baseUrl, 'https') ? 'https' : 'http');

        /*
         * The layout falls back to request()->url() for the canonical and og:url
         * tags. Outside a web request Laravel resolves that to a Request built
         * from app.url when the container was first resolved, so forcing the URL
         * generator's root is not enough: the stale request instance still answers
         * with the dev host. Rebinding it to a request for the export's own
         * address is what actually moves those two tags.
         */
        $this->laravel->instance('request', Request::create($baseUrl, 'GET'));
    }

    /**
     * Render the single page.
     *
     * The data comes from HomeController::pageData() rather than a copy of it, so
     * adding a section to the page cannot leave the static build quietly missing
     * it.
     *
     * An empty error bag is supplied because ShareErrorsFromSession only runs on
     * HTTP requests, and the page reads $errors to decide whether a field is
     * marked invalid. A first visit has no errors, which is what an empty bag
     * describes.
     */
    private function renderHome(): string
    {
        $controller = new HomeController;

        $html = View::make('pages.single', [
            ...$controller->pageData(app(PortfolioService::class)),
            'errors' => new ViewErrorBag,
        ])->render();

        return $this->rewriteForm($this->rewriteUrls($html));
    }

    private function renderRobots(string $baseUrl): string
    {
        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            '',
            'Sitemap: '.$baseUrl.'/sitemap.xml',
            '',
        ]);
    }

    private function renderSitemap(string $baseUrl): string
    {
        $urls = [
            ['loc' => $baseUrl, 'priority' => '1.0', 'freq' => 'weekly'],
            ['loc' => $baseUrl.'/resume', 'priority' => '0.6', 'freq' => 'monthly'],
        ];

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($urls as $url) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>';
            $xml[] = '    <changefreq>'.$url['freq'].'</changefreq>';
            $xml[] = '    <priority>'.$url['priority'].'</priority>';
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml)."\n";
    }

    /**
     * Long cache for fingerprinted assets, none for the HTML.
     *
     * Without this every visit revalidates the whole page, and the CSS and JS
     * hashes change on each build, so a stale cache would serve an old page
     * pointing at assets that no longer exist.
     */
    private function headers(): string
    {
        return implode("\n", [
            '/build/*',
            '  Cache-Control: public, max-age=31536000, immutable',
            '',
            '/*.html',
            '  Cache-Control: public, max-age=0, must-revalidate',
            '',
            '/',
            '  Cache-Control: public, max-age=0, must-revalidate',
            '',
        ]);
    }

    /**
     * Copy the CV so /resume is a real file rather than a route that no longer
     * exists. It lands beside index.html with a download attribute in the markup
     * instead of the Content-Disposition header the controller used to send.
     */
    private function copyCv(string $output): int
    {
        $profile = Profile::current();
        $path = $profile?->cv_path;

        if (blank($path) || ! Storage::disk('public')->exists($path)) {
            $this->components->warn('No CV is set on the profile, so /resume will 404.');

            return 0;
        }

        $target = $output.'/resume.pdf';
        $this->ensureDirectory(dirname($target));
        copy(Storage::disk('public')->path($path), $target);

        $this->components->twoColumnDetail('resume.pdf', 'copied');

        return 1;
    }

    /**
     * Copy everything under the public disk. Storage::url() renders these as
     * /storage/..., so they keep the same layout inside the export and the URLs
     * in the HTML need no rewriting at all.
     */
    private function copyMedia(string $output): int
    {
        $disk = Storage::disk('public');
        $count = 0;

        foreach ($disk->allFiles() as $file) {
            $target = $output.'/storage/'.$file;

            $this->ensureDirectory(dirname($target));
            copy($disk->path($file), $target);
            $count++;
        }

        $this->components->twoColumnDetail('storage/', "{$count} image(s) and uploads");

        return $count;
    }

    private function copyBuildAssets(string $output): int
    {
        $source = public_path('build');

        if (! is_dir($source)) {
            $this->components->warn('public/build is missing. Run "npm run build" first, or the export will have no CSS.');

            return 0;
        }

        $count = 0;

        /** @var \SplFileInfo $file */
        foreach (Finder::create()->files()->in($source) as $file) {
            $relative = Str::after($file->getRealPath(), realpath($source).DIRECTORY_SEPARATOR);
            $target = $output.'/build/'.$relative;

            $this->ensureDirectory(dirname($target));
            copy($file->getRealPath(), $target);
            $count++;
        }

        $this->components->twoColumnDetail('build/', "{$count} compiled asset(s)");

        return $count;
    }

    /**
     * Make every URL on the page resolve on whatever host serves the files.
     *
     * The export does not know the final address, and guessing it is what broke
     * the first deploy: the build was made for a domain that turned out to belong
     * to somebody else, so the page loaded its CSS and images from their site
     * while the real files sat unused on the server this build was uploaded to.
     *
     * Reducing the base URL to a root-relative path removes the guesswork
     * entirely. A page served from any host, on any domain, with no rebuild,
     * requests its own files. Only the two tags that are meaningless without a
     * host, canonical and og:url, are left absolute, and those come from the
     * --url option being passed at build time.
     */
    private function rewriteUrls(string $html): string
    {
        $base = $this->baseUrl();
        $canonical = [
            e(route('resume')) => '/resume.pdf',
            route('resume') => '/resume.pdf',
        ];

        if ($endpoint = $this->formspreeEndpoint()) {
            $canonical[e(route('contact.store'))] = $endpoint;
            $canonical[route('contact.store')] = $endpoint;
        }

        /*
         * Applied first, and only where the base URL is a whole host prefix, so
         * the form endpoint and the sitemap entry keep their own address. Leaving
         * them absolute is deliberate: they point at a third party, not at this
         * site's own files.
         */
        $html = strtr($html, $canonical);

        // The leading slash is kept so the path stays root-relative rather than
        // becoming relative to whatever directory the page happens to sit in.
        return str_replace($base.'/', '/', $html);
    }

    /**
     * The form cannot post to a file server, so it goes to Formspree instead.
     *
     * The CSRF input has to go with it: Laravel's token is bound to a session
     * that does not exist on a static host, and Formspree would reject it.
     */
    private function rewriteForm(string $html): string
    {
        if (! $this->formspreeConfigured()) {
            return $html;
        }

        $html = preg_replace(
            '/<input[^>]*name="_token"[^>]*>\s*/i',
            '',
            $html
        ) ?? $html;

        // The old "message sent" banner is a redirect artefact and has no static equivalent.
        return preg_replace(
            '/<div class="alert alert--ok"[^>]*>.*?<\/div>/s',
            '',
            $html
        ) ?? $html;
    }

    private function formspreeEndpoint(): ?string
    {
        $endpoint = env('FORMSPREE_ENDPOINT');

        return filled($endpoint) ? $endpoint : null;
    }

    private function formspreeConfigured(): bool
    {
        return $this->formspreeEndpoint() !== null;
    }

    private function write(string $path, string $contents): int
    {
        $this->ensureDirectory(dirname($path));
        file_put_contents($path, $contents);

        $this->components->twoColumnDetail(basename($path), number_format(strlen($contents)).' bytes');

        return 1;
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0o755, true);
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        /** @var \SplFileInfo $file */
        foreach (Finder::create()->in($path) as $file) {
            $file->isDir() && ! $file->isLink()
                ? @rmdir($file->getRealPath())
                : @unlink($file->getRealPath());
        }

        @rmdir($path);
    }
}
