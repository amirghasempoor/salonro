<?php

namespace App\Models;

use Database\Factories\SmsHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsHistory extends Model
{
    /** @use HasFactory<SmsHistoryFactory> */
    use HasFactory;

    protected $guarded = ['id'];
}
