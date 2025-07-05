<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnderConstructionSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'start_date',
        'end_date',
        'blurb',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
}
