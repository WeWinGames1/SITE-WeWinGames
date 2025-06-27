<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPage extends Model
{
    //
    protected $fillable = [
        'title',
        'slug',
        'content',
        'featured_image',
        'published',
    ];
}
