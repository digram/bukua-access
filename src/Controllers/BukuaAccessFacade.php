<?php

namespace BukuaAccess\Controllers;

use Illuminate\Support\Facades\Facade;

class BukuaAccessFacade extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'bukua_access';
    }
}
