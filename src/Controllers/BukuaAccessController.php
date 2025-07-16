<?php

namespace BukuaAccess\Controllers;

use Illuminate\Routing\Controller;
use BukuaAccess\Traits\AuthenticatesWithToken;
use BukuaAccess\Traits\Counties;
use BukuaAccess\Traits\Subjects;
use BukuaAccess\Traits\Schools;

class BukuaAccessController extends Controller
{
    use AuthenticatesWithToken;
    use Counties;
    use Subjects;
    use Schools;

    protected string $baseUrl;
    protected string $tokenCacheKey = 'bukua_access_token';
    protected int $tokenCacheTtl = 3600 * 24 * 365;

    public function __construct()
    {
        $this->baseUrl = config('services.bukua_access.base_url');
    }
}
