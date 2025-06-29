<?php
// app/Http/Controllers/BettingEducationController.php
namespace App\Http\Controllers;

use App\Services\PageService;
use Inertia\Inertia;

class BettingEducationController extends Controller
{
    public function __invoke(PageService $pages)
    {
        $allPages = $pages->getAllUnpaginated();
        return Inertia::render('BettingEducation', [
            'pages' => $allPages->map->only(['id', 'title', 'slug', 'featured_image', 'published']),
        ]);
    }
}