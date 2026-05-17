<?php

namespace App\Models;

use Database\Factories\OtpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    /** @use HasFactory<OtpFactory> */
    use HasFactory;

    protected $guarded = ['id'];
}
