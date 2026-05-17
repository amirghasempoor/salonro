<?php

namespace App\Models;

use Database\Factories\HallFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    /** @use HasFactory<HallFactory> */
    use HasFactory;

    protected $guarded = ['id'];
}
