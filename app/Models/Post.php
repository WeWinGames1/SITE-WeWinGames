<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'user_id',
        'is_published',
        'published_at',
        'meta',
        'category',
        'tags',
        'views_count',
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'meta' => 'array',
        'tags' => 'array',
        'views_count' => 'integer',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from title if not provided
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = static::generateUniqueSlug($post->title);
            }
        });

        // Update slug if title changes and slug wasn't manually set
        static::updating(function ($post) {
            if ($post->isDirty('title') && ! $post->isDirty('slug')) {
                $post->slug = static::generateUniqueSlug($post->title, $post->id);
            }
        });
    }

    /**
     * Generate a unique slug.
     */
    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Get the author of the post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope for published posts.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope for draft posts.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('is_published', false);
    }

    /**
     * Scope for scheduled posts.
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where('published_at', '>', now());
    }

    /**
     * Scope for posts by category.
     */
    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for posts with tag.
     */
    public function scopeWithTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Get the status of the post.
     */
    public function getStatusAttribute(): string
    {
        if (! $this->is_published) {
            return 'draft';
        }

        if ($this->published_at && $this->published_at->isFuture()) {
            return 'scheduled';
        }

        return 'published';
    }

    /**
     * Append status and featured_image_url to the model.
     */
    protected $appends = ['status', 'featured_image_url', 'reading_time'];

    /**
     * Get the excerpt or generate from content.
     */
    public function getExcerptAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // Generate excerpt from content
        $plainContent = strip_tags($this->content);

        return Str::limit($plainContent, 160);
    }

    /**
     * Get the reading time in minutes.
     */
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $wordsPerMinute = 200;

        return max(1, ceil($wordCount / $wordsPerMinute));
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured-image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->sharpen(10)
            ->optimize()
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(1200)
            ->height(630)
            ->optimize()
            ->nonQueued();
    }

    /**
     * Get the featured image URL.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        // First check if media library has an image
        $media = $this->getFirstMedia('featured-image');
        if ($media) {
            return $media->getUrl();
        }

        // Fall back to old featured_image field
        if (! $this->featured_image) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->featured_image, FILTER_VALIDATE_URL)) {
            return $this->featured_image;
        }

        // Check if this is a media library reference (media/{id}/{filename})
        if (str_starts_with($this->featured_image, 'media/')) {
            return asset('storage/'.$this->featured_image);
        }

        // Otherwise, assume it's a path and build the URL
        // Remove leading slash to prevent double slashes in URL
        $imagePath = ltrim($this->featured_image, '/');

        return asset('storage/'.$imagePath);
    }

    /**
     * Increment the view count.
     */
    public function incrementViewCount(): void
    {
        $this->increment('views_count');
    }

    /**
     * Get available categories.
     */
    public static function getCategories(): array
    {
        // Try to get categories from database first
        try {
            $categories = BlogCategory::active()
                ->ordered()
                ->pluck('name', 'slug')
                ->toArray();

            if (! empty($categories)) {
                return $categories;
            }
        } catch (\Exception $e) {
            // Fall back to hardcoded if database is not ready
        }

        // Fallback categories
        return [
            'betting-education' => 'Betting Education',
            'sports-analysis' => 'Sports Analysis',
            'industry-news' => 'Industry News',
            'tips-strategies' => 'Tips & Strategies',
            'beginners-guide' => 'Beginners Guide',
            'advanced-betting' => 'Advanced Betting',
        ];
    }

    /**
     * Get popular tags.
     */
    public static function getPopularTags(int $limit = 20): array
    {
        return static::published()
            ->whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take($limit)
            ->keys()
            ->toArray();
    }
}
