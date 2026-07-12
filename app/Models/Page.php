<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    public const RENDER_NORMAL = 'normal';

    public const RENDER_INERTIA_RAW = 'inertia_raw';

    public const RENDER_BLADE_RAW = 'blade_raw';

    public const RENDER_MODES = [self::RENDER_NORMAL, self::RENDER_INERTIA_RAW, self::RENDER_BLADE_RAW];

    protected $fillable = ['title', 'slug', 'content', 'render_mode', 'raw_html', 'featured_image', 'published'];

    protected $appends = ['featured_image_url'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published' => 'boolean',
        ];
    }

    /**
     * Whether this page is served as a full-page, standalone HTML document (scripts execute).
     */
    public function usesBladeRaw(): bool
    {
        return $this->render_mode === self::RENDER_BLADE_RAW;
    }

    /**
     * Whether this page is rendered inside the Inertia shell but without site header/footer chrome.
     */
    public function usesInertiaRaw(): bool
    {
        return $this->render_mode === self::RENDER_INERTIA_RAW;
    }

    /**
     * The HTML that should be echoed for a full-page raw render.
     */
    public function rawHtmlOrContent(): string
    {
        return (string) ($this->raw_html ?: $this->content);
    }

    /**
     * Get the full URL for the featured image.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (! $this->featured_image) {
            return null;
        }

        // Check if this is a media library reference (media/{id}/{filename})
        if (str_starts_with($this->featured_image, 'media/')) {
            return asset('storage/'.$this->featured_image);
        }

        // Otherwise it's a regular storage path
        return asset('storage/'.$this->featured_image);
    }
}
