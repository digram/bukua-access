<?php

namespace BukuaAccess\Traits;

trait Subjects
{
    public function subjects(int $page, int $per_page)
    {
        return $this->makeAuthenticatedRequest('api/v1/subjects', [
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }
}
