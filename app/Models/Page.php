<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'featured_image', 'published'];

    protected $appends = ['featured_image_url'];

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
