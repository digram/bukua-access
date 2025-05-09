<?php

namespace BukuaAccess\Facades;

use Illuminate\Support\Facades\Facade;

class BukuaAccess extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'bukuaaccess';
    }
}
