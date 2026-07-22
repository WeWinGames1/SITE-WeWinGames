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
            'content' => 'nullable|string',
            'render_mode' => 'nullable|string|in:'.implode(',', Page::RENDER_MODES),
            'raw_html' => 'nullable|string',
            'html_file' => 'nullable|file|mimes:html,htm,txt|max:5120',
            'featured_image' => 'nullable|image|max:20480',
            'featured_image_media_id' => 'nullable|integer|exists:media,id',
            'published' => 'boolean',
        ]);

        $data = $this->normalizeContent($request, $data);

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

        unset($data['featured_image_media_id'], $data['html_file']);
        $this->pages->create($data);

        return redirect()->route('admin.pages.index');
    }

    public function edit(Page $page)
    {
        return Inertia::render('admin/PageEdit', [
            'page' => $page,
            'assets' => \App\Models\Media::assetsFor('page_id', $page->id),
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,'.$page->id,
            'content' => 'nullable|string',
            'render_mode' => 'nullable|string|in:'.implode(',', Page::RENDER_MODES),
            'raw_html' => 'nullable|string',
            'html_file' => 'nullable|file|mimes:html,htm,txt|max:5120',
            'featured_image' => 'nullable|image|max:20480',
            'featured_image_media_id' => 'nullable|integer|exists:media,id',
            'published' => 'boolean',
        ]);

        $data = $this->normalizeContent($request, $data);

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

        unset($data['featured_image_media_id'], $data['html_file']);
        $this->pages->update($page, $data);

        return redirect()->route('admin.pages.index');
    }

    /**
     * Resolve render_mode, uploaded HTML file, and non-null content/raw_html.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeContent(Request $request, array $data): array
    {
        $data['render_mode'] = $data['render_mode'] ?? Page::RENDER_NORMAL;

        // An uploaded .html file overrides the pasted raw HTML.
        if ($request->hasFile('html_file')) {
            $data['raw_html'] = (string) file_get_contents($request->file('html_file')->getRealPath());
        }

        // The pages.content / landing_pages.content columns are NOT NULL.
        $data['content'] = $data['content'] ?? ($data['raw_html'] ?? '');

        if ($data['render_mode'] === Page::RENDER_NORMAL) {
            $data['raw_html'] = null;
        }

        return $data;
    }

    public function destroy(Page $page)
    {
        $this->pages->delete($page);

        return redirect()->route('admin.pages.index');
    }
}
