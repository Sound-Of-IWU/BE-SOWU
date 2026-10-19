<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JoinTime extends Model
{
    use HasFactory;

    protected $table = 'join_times';

    protected $fillable = [
        'title',
        'open_at',
        'close_at',
        'is_active',
        'description',
    ];

    protected $casts = [
        'open_at' => 'datetime',
        'close_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
