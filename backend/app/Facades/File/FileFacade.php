<?php

namespace App\Facades\File;

use Illuminate\Support\Facades\Facade;

class FileFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return File::class;
    }
}
