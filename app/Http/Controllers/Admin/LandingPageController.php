<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Services\LandingPageService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LandingPageController extends Controller
{
    protected $pages;

    public function __construct(LandingPageService $pages)
    {
        $this->pages = $pages;
    }

    public function index()
    {
        $pages = $this->pages->all();
        return Inertia::render('admin/LandingPagesIndex', ['pages' => $pages]);
    }

    public function create()
    {
        return Inertia::render('admin/LandingPageEdit', ['page' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:landing_pages,slug',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:2048',
            'published' => 'boolean',
        ]);
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('landing_pages', 'public');
        }
        $this->pages->create($data);
        return redirect()->route('admin.landing-pages.index');
    }

    public function edit(LandingPage $page)
    {
        return Inertia::render('admin/LandingPageEdit', ['page' => $page]);
    }

    public function update(Request $request, LandingPage $page)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:landing_pages,slug,' . $page->id,
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:2048',
            'published' => 'boolean',
        ]);
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('landing_pages', 'public');
        } else {
            unset($data['featured_image']);
        }
        $this->pages->update($page, $data);
        return redirect()->route('admin.landing-pages.index');
    }

    public function destroy(LandingPage $page)
    {
        $this->pages->delete($page);
        return redirect()->route('admin.landing-pages.index');
    }
}
