<?php

namespace BukuaAccess\Traits;

trait Counties
{
    public function counties(int $page, int $per_page)
    {
        return $this->makeAuthenticatedRequest('api/v1/counties', [
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }
}
