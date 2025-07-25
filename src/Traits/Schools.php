<?php

namespace BukuaAccess\Traits;

trait Schools
{
    public function schools(int $page, int $per_page)
    {
        return $this->makeAuthenticatedRequest('api/v1/schools', [
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }

    public function schoolsWithSubjects(int $page, int $per_page)
    {
        return $this->makeAuthenticatedRequest('api/v1/schools/subjects', [
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }

    public function schoolsWithSubjectCombinations(int $page, int $per_page)
    {
        return $this->makeAuthenticatedRequest('api/v1/schools/subject-combinations', [
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }

    public function schoolsWithProfiles(int $page, int $per_page)
    {
        return $this->makeAuthenticatedRequest('api/v1/schools/profiles', [
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }

    public function schoolsWithDepartments(int $page, int $per_page)
    {
        return $this->makeAuthenticatedRequest('api/v1/schools/departments', [
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }
}
