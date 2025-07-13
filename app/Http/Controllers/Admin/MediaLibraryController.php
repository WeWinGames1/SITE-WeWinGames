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
                'full_url' => $item->getUrl(),
                'thumb_url' => $item->getUrl(),
                'preview_url' => $item->getUrl(),
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
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|image|max:20480', // 20MB max
        ]);

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
                    'full_url' => Storage::disk('public')->url($storedPath),
                    'thumb_url' => Storage::disk('public')->url($storedPath), // Same as full for now
                    'preview_url' => Storage::disk('public')->url($storedPath), // Same as full for now
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
        // Delete the file from storage
        $filePath = "media/{$media->id}/{$media->file_name}";
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
        
        // Delete the media record
        $media->delete();

        return response()->json([
            'message' => 'Media deleted successfully',
        ]);
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
            $filePath = "media/{$item->id}/{$item->file_name}";
            $url = Storage::disk('public')->url($filePath);
            
            return array_merge($item->toArray(), [
                'full_url' => $url,
                'thumb_url' => $url,
                'preview_url' => $url,
            ]);
        });

        return response()->json($media);
    }
}