<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vismis extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipe',
        'fill',
        'credit',
        'image',
    ];
}
