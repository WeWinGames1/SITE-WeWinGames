<?php
// app/Services/PageService.php
namespace App\Services;

use App\Models\Page;

class PageService
{
    public function all()
    {
        return Page::with('author')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }
    
    public function getAllUnpaginated()
    {
        return Page::with('author')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findById($id)
    {
        return Page::findOrFail($id);
    }

    public function create(array $data)
    {
        return Page::create($data);
    }

    public function update(Page $page, array $data)
    {
        $page->update($data);
        return $page;
    }

    public function delete(Page $page)
    {
        return $page->delete();
    }
}