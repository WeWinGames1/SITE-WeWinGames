<?php

namespace App\Services;

use App\Models\LandingPage;

/**
 * Class LandingPageService.
 */
class LandingPageService
{
    public function all()
    {
        return LandingPage::orderBy('created_at', 'desc')->get();
    }

    public function findById($id)
    {
        return LandingPage::findOrFail($id);
    }

    public function create(array $data)
    {
        return LandingPage::create($data);
    }

    public function update(LandingPage $page, array $data)
    {
        $page->update($data);
        return $page;
    }

    public function delete(LandingPage $page)
    {
        return $page->delete();
    }
}
