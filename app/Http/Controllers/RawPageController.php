<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Page;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class RawPageController extends Controller
{
    /**
     * Relaxed CSP for admin-authored full-page raw HTML. These pages are meant
     * to control the entire document and embed arbitrary (trusted) third-party
     * scripts/styles/fonts/APIs, so the site's strict allowlist CSP does not
     * apply here. Still blocks plugins and mixed content.
     */
    private const RAW_CSP = "default-src 'self' https: data: blob: 'unsafe-inline' 'unsafe-eval'; "
        ."script-src 'self' https: 'unsafe-inline' 'unsafe-eval'; "
        ."style-src 'self' https: 'unsafe-inline'; "
        ."img-src 'self' https: data: blob:; "
        ."font-src 'self' https: data:; "
        ."connect-src 'self' https:; "
        ."frame-src 'self' https:; "
        ."object-src 'none'; base-uri 'self'";

    /**
     * Render a page/landing page whose render_mode is "blade_raw" as a
     * standalone HTML document, OUTSIDE the Inertia/Vue app, so that any
     * <script> tags the admin pasted actually execute. Site-wide tracking is
     * injected so these pages are still covered by analytics/attribution.
     */
    public function render(Page|LandingPage $page, bool $withTracking = true): Response
    {
        $html = $page->rawHtmlOrContent();
        $trackingHead = $withTracking ? view('partials.tracking-head')->render() : '';
        $trackingBody = $withTracking ? view('partials.tracking-body')->render() : '';

        // When the admin pasted a full HTML document, inject tracking into the
        // existing <head>/<body> so we never render a nested document.
        if (Str::contains($html, '<html', ignoreCase: true)) {
            $html = $this->injectBeforeHeadClose($html, $trackingHead);
            $html = $this->injectAfterBodyOpen($html, $trackingBody);

            return response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Content-Security-Policy', self::RAW_CSP);
        }

        // Otherwise treat it as a body fragment and wrap it in a minimal scaffold.
        return response()->view('raw-page', [
            'page' => $page,
            'body' => $html,
            'trackingHead' => $trackingHead,
            'trackingBody' => $trackingBody,
        ])->header('Content-Security-Policy', self::RAW_CSP);
    }

    /**
     * Insert markup immediately before the closing </head> tag (case-insensitive).
     * Uses a callback so the injected markup is treated literally (no $n / \n
     * backreference interpretation). Falls back to prepending when absent.
     */
    private function injectBeforeHeadClose(string $html, string $markup): string
    {
        if (preg_match('/<\/head>/i', $html)) {
            return preg_replace_callback('/<\/head>/i', fn (array $m): string => $markup."\n".$m[0], $html, 1);
        }

        return $markup.$html;
    }

    /**
     * Insert markup immediately after the opening <body ...> tag (case-insensitive).
     * Uses a callback so the injected markup is treated literally. Falls back to
     * appending when no <body> is present.
     */
    private function injectAfterBodyOpen(string $html, string $markup): string
    {
        if (preg_match('/<body[^>]*>/i', $html)) {
            return preg_replace_callback('/<body[^>]*>/i', fn (array $m): string => $m[0]."\n".$markup, $html, 1);
        }

        return $html.$markup;
    }
}
