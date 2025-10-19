<?php

namespace BukuaAccess\Traits;

trait General
{
    public function academicYear()
    {
        return $this->makeAuthenticatedRequest('/api/v1/academic-year');
    }
}
