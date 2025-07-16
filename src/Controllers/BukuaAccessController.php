<?php

namespace BukuaAccess\Controllers;

use Illuminate\Routing\Controller;
use BukuaAccess\Traits\AuthenticatesWithToken;

class BukuaAccessController extends Controller
{
    use AuthenticatesWithToken;

    protected string $baseUrl;
    protected string $tokenCacheKey = 'bukua_access_token';
    protected int $tokenCacheTtl = 3600 * 24 * 365;

    public function __construct()
    {
        $this->baseUrl = config('services.bukua_access.base_url');
    }

    public function counties(int $page, int $per_page)
    {
        return $this->makeAuthenticatedRequest('api/v1/counties', [
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }

    public function subjects(int $page, int $per_page)
    {
        return $this->makeAuthenticatedRequest('api/v1/subjects', [
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }
}
