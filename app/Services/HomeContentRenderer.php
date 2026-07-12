<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Renders the editable homepage HTML: resolves {{stat:KEY}} placeholders from
 * live homepage data and splits the markup into ordered segments so live Vue
 * widgets ([pricing], [testimonials], [todays-bets]) can be mounted between
 * HTML fragments. This keeps the layout/copy freely editable while the dynamic
 * numbers and widgets stay wired to the real data.
 */
class HomeContentRenderer
{
    /**
     * Widget shortcodes that map to live Vue components on the frontend.
     */
    public const WIDGETS = ['pricing', 'testimonials', 'todays-bets'];

    /**
     * Replace {{stat:KEY}} tokens with values from the provided stat map.
     *
     * @param  array<string, scalar|null>  $stats
     */
    public function resolvePlaceholders(string $html, array $stats): string
    {
        return preg_replace_callback('/\{\{\s*stat:([a-zA-Z0-9_]+)\s*\}\}/', function (array $m) use ($stats) {
            $key = $m[1];

            return array_key_exists($key, $stats) ? (string) $stats[$key] : $m[0];
        }, $html) ?? $html;
    }

    /**
     * Split the HTML into ordered segments of HTML and widget placeholders.
     * Widget shortcodes look like [pricing] / [testimonials] / [todays-bets].
     *
     * @return array<int, array{type: string, value?: string, name?: string}>
     */
    public function segments(string $html): array
    {
        $pattern = '/\[('.implode('|', array_map('preg_quote', self::WIDGETS)).')\]/';

        $parts = preg_split($pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return [['type' => 'html', 'value' => $html]];
        }

        $segments = [];
        foreach ($parts as $index => $part) {
            // Odd indexes are the captured widget names.
            if ($index % 2 === 1) {
                $segments[] = ['type' => 'widget', 'name' => $part];

                continue;
            }

            if ($part !== '') {
                $segments[] = ['type' => 'html', 'value' => $part];
            }
        }

        return $segments;
    }

    /**
     * Render editable homepage content: resolve stats then split into segments.
     *
     * @param  array<string, scalar|null>  $stats
     * @return array<int, array{type: string, value?: string, name?: string}>
     */
    public function render(string $html, array $stats): array
    {
        return $this->segments($this->resolvePlaceholders($html, $stats));
    }

    /**
     * List of the supported {{stat:*}} tokens, for the admin editor helper.
     *
     * @param  array<string, scalar|null>  $stats
     * @return array<int, string>
     */
    public function availableTokens(array $stats): array
    {
        return array_map(fn ($key) => 'stat:'.$key, array_keys($stats));
    }

    /**
     * Whether the HTML contains any widget shortcodes.
     */
    public function hasWidgets(string $html): bool
    {
        return (bool) Str::of($html)->matchAll('/\[('.implode('|', self::WIDGETS).')\]/')->count();
    }
}
