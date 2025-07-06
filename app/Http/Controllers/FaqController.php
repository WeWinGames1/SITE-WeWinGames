<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Inertia\Inertia;

class FaqController extends Controller
{
    /**
     * Display the FAQ page.
     */
    public function index()
    {
        $faqs = Faq::getGroupedByCategory();
        
        return Inertia::render('Faq', [
            'faqs' => $faqs,
            'categories' => array_keys($faqs->toArray())
        ]);
    }
}