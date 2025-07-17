<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MediaLibraryController extends Controller
{
    /**
     * Display the media library.
     */
    public function index(): Response
    {
        $media = Media::where(function ($query) {
                $query->where('model_type', 'library')
                    ->orWhere('collection_name', 'library')
                    ->orWhere('collection_name', 'featured-image');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(24);

        // Transform the collection to ensure URLs are included
        $media->getCollection()->transform(function ($item) {
            return array_merge($item->toArray(), [
                'full_url' => $item->full_url,
                'thumb_url' => $item->thumb_url,
                'preview_url' => $item->preview_url,
            ]);
        });

        return Inertia::render('admin/MediaLibrary/Index', [
            'media' => $media,
        ]);
    }

    /**
     * Upload new media files.
     */
    public function store(Request $request): JsonResponse
    {
        // Log the request for debugging
        \Log::info('Media upload request', [
            'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
            'has_files_array' => $request->has('files'),
            'all_files' => $request->allFiles(),
        ]);
        
        try {
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'required|image|max:20480', // 20MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Media upload validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'files' => $request->allFiles(),
            ]);
            throw $e;
        }

        $uploadedMedia = [];

        foreach ($request->file('files') as $file) {
            try {
                // Generate a unique filename
                $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '-', $file->getClientOriginalName());
                
                // Create media entry first to get the ID
                $media = Media::create([
                    'model_type' => 'library', // Use 'library' as a placeholder type
                    'model_id' => 0, // Use 0 as placeholder ID
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'collection_name' => 'library',
                    'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_name' => $fileName,
                    'mime_type' => $file->getMimeType(),
                    'disk' => 'public',
                    'conversions_disk' => 'public',
                    'size' => $file->getSize(),
                    'manipulations' => [],
                    'custom_properties' => [],
                    'generated_conversions' => [],
                    'responsive_images' => [],
                    'order_column' => 1,
                ]);
                
                // Now store the file using the media ID
                $mediaPath = "media/{$media->id}";
                $storedPath = $file->storeAs($mediaPath, $fileName, 'public');

                $uploadedMedia[] = [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'full_url' => $media->full_url,
                    'thumb_url' => $media->thumb_url,
                    'preview_url' => $media->preview_url,
                    'created_at' => $media->created_at,
                ];
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error uploading file: ' . $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Files uploaded successfully',
            'media' => $uploadedMedia,
        ]);
    }

    /**
     * Delete media.
     */
    public function destroy(Media $media): JsonResponse
    {
        try {
            \Log::info('Attempting to delete media', [
                'media_id' => $media->id,
                'file_name' => $media->file_name,
                'collection' => $media->collection_name,
                'model_type' => $media->model_type,
            ]);

            // Check if media is being used by other models
            $usage = $this->checkMediaUsage($media);
            if (!empty($usage)) {
                \Log::warning('Media is still in use', [
                    'media_id' => $media->id,
                    'usage' => $usage,
                ]);
                
                return response()->json([
                    'message' => 'Cannot delete media - it is currently being used by: ' . implode(', ', $usage),
                ], 422);
            }

            // Try to delete the file from storage
            $filePath = "media/{$media->id}/{$media->file_name}";
            if (Storage::disk('public')->exists($filePath)) {
                if (!Storage::disk('public')->delete($filePath)) {
                    \Log::error('Failed to delete media file from storage', [
                        'media_id' => $media->id,
                        'file_path' => $filePath,
                    ]);
                    
                    return response()->json([
                        'message' => 'Failed to delete media file from storage',
                    ], 500);
                }
                \Log::info('Media file deleted from storage', ['file_path' => $filePath]);
            } else {
                \Log::warning('Media file not found in storage', [
                    'media_id' => $media->id,
                    'file_path' => $filePath,
                ]);
            }
            
            // Also try to delete the entire media directory if it exists and is empty
            $mediaDir = "media/{$media->id}";
            if (Storage::disk('public')->exists($mediaDir)) {
                $files = Storage::disk('public')->files($mediaDir);
                if (empty($files)) {
                    Storage::disk('public')->deleteDirectory($mediaDir);
                    \Log::info('Empty media directory deleted', ['directory' => $mediaDir]);
                }
            }
            
            // Delete the media record from database
            $media->delete();
            
            \Log::info('Media deleted successfully', ['media_id' => $media->id]);

            return response()->json([
                'message' => 'Media deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error deleting media', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Error deleting media: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if media is being used by other models.
     */
    private function checkMediaUsage(Media $media): array
    {
        $usage = [];
        
        // Check if used by blog posts
        if ($media->collection_name === 'featured-image') {
            $posts = \App\Models\Post::where('featured_image', 'like', "%{$media->id}%")->count();
            if ($posts > 0) {
                $usage[] = "blog posts ({$posts})";
            }
        }
        
        // Check if used in blog post content
        $postsWithContent = \App\Models\Post::where('content', 'like', "%{$media->getUrl()}%")->count();
        if ($postsWithContent > 0) {
            $usage[] = "blog post content ({$postsWithContent})";
        }
        
        // You can add more usage checks here for other models
        
        return $usage;
    }

    /**
     * Get media for picker modal.
     */
    public function picker(Request $request): JsonResponse
    {
        $query = Media::where(function ($q) {
            $q->where('model_type', 'library')
                ->orWhere('collection_name', 'library')
                ->orWhere('collection_name', 'featured-image');
        });

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $media = $query->orderBy('created_at', 'desc')
            ->paginate(12);

        // Transform the media items to include proper URLs
        $media->getCollection()->transform(function ($item) {
            return array_merge($item->toArray(), [
                'full_url' => $item->full_url,
                'thumb_url' => $item->thumb_url,
                'preview_url' => $item->preview_url,
            ]);
        });

        return response()->json($media);
    }
}