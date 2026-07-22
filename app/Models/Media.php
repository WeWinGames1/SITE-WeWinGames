<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'manipulations' => 'array',
        'custom_properties' => 'array',
        'generated_conversions' => 'array',
        'responsive_images' => 'array',
    ];

    /**
     * Override the getUrl method for library items
     */
    public function getUrl(string $conversionName = ''): string
    {
        // For library items, construct the URL manually
        if ($this->model_type === 'library' || ($this->model_type === null && $this->collection_name === 'library')) {
            return asset('storage/media/'.$this->id.'/'.$this->file_name);
        }

        // For other items, use the parent method
        return parent::getUrl($conversionName);
    }

    /**
     * Get the full URL for the media item.
     */
    public function getFullUrlAttribute(): string
    {
        return $this->getUrl();
    }

    /**
     * Get the thumbnail URL for the media item.
     */
    public function getThumbUrlAttribute(): string
    {
        // For now, return the full URL for library items
        if ($this->model_type === 'library' || ($this->model_type === null && $this->collection_name === 'library')) {
            return $this->getUrl();
        }

        return $this->getUrl('thumb');
    }

    /**
     * Get the preview URL for the media item.
     */
    public function getPreviewUrlAttribute(): string
    {
        // For now, return the full URL for library items
        if ($this->model_type === 'library' || ($this->model_type === null && $this->collection_name === 'library')) {
            return $this->getUrl();
        }

        return $this->getUrl('preview');
    }

    /**
     * Append custom attributes.
     */
    protected $appends = ['full_url', 'thumb_url', 'preview_url'];

    /**
     * Library media assigned to a page or landing page via custom_properties.
     *
     * @param  string  $ownerKey  'page_id' or 'landing_page_id'
     * @return array<int, array<string, mixed>>
     */
    public static function assetsFor(string $ownerKey, int $ownerId): array
    {
        return static::where('model_type', 'library')
            ->where('custom_properties->'.$ownerKey, $ownerId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (self $media) => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'full_url' => $media->full_url,
                'thumb_url' => $media->thumb_url,
            ])
            ->values()
            ->all();
    }
}
