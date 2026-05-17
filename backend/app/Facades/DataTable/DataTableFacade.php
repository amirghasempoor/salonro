<?php

namespace App\Facades\DataTable;

use Illuminate\Support\Facades\Facade;

class DataTableFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DataTable::class;
    }
}
