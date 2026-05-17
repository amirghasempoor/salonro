<?php

namespace App\Models;

use Database\Factories\ExpertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expert extends Model
{
    /** @use HasFactory<ExpertFactory> */
    use HasFactory;

    protected $guarded = ['id'];
}
