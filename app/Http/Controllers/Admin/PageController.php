<?php

// app/Http/Controllers/Admin/PageController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\PageService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    protected $pages;

    public function __construct(PageService $pages)
    {
        $this->pages = $pages;
    }

    public function index()
    {
        $pages = $this->pages->all();

        return Inertia::render('admin/PagesIndex', ['pages' => $pages->items()]);
    }

    public function create()
    {
        return Inertia::render('admin/PageEdit', ['page' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:20480',
            'featured_image_media_id' => 'nullable|integer|exists:media,id',
            'published' => 'boolean',
        ]);

        // Handle media library selection
        if ($request->filled('featured_image_media_id')) {
            $media = \App\Models\Media::find($request->featured_image_media_id);
            if ($media) {
                // Store the direct path reference
                $data['featured_image'] = 'media/'.$media->id.'/'.$media->file_name;
            }
        } elseif ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('pages', 'public');
        }

        unset($data['featured_image_media_id']);
        $this->pages->create($data);

        return redirect()->route('admin.pages.index');
    }

    public function edit(Page $page)
    {
        return Inertia::render('admin/PageEdit', ['page' => $page]);
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,'.$page->id,
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:20480',
            'featured_image_media_id' => 'nullable|integer|exists:media,id',
            'published' => 'boolean',
        ]);

        // Handle media library selection
        if ($request->filled('featured_image_media_id')) {
            $media = \App\Models\Media::find($request->featured_image_media_id);
            if ($media) {
                // Store the direct path reference
                $data['featured_image'] = 'media/'.$media->id.'/'.$media->file_name;
            }
        } elseif ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('pages', 'public');
        } else {
            unset($data['featured_image']);
        }

        unset($data['featured_image_media_id']);
        $this->pages->update($page, $data);

        return redirect()->route('admin.pages.index');
    }

    public function destroy(Page $page)
    {
        $this->pages->delete($page);

        return redirect()->route('admin.pages.index');
    }
}
