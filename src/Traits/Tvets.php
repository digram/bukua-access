<?php

namespace BukuaAccess\Traits;

trait Tvets
{
    public function tvets(int $page, int $per_page)
    {
        return $this->makeAuthenticatedRequest('/api/v1/tvets', [
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }
}
