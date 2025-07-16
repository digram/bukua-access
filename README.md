# Bukua Edtech API Services for Laravel

A Laravel package for integrating with Bukua Edtech API services, providing easy access to schools data.

## Features

- Authentication with Bukua API using client credentials
- Simple methods to fetch paginated schools data

## Prerequisites  

**Bukua Developer Account**:  
   - Create a **Core Access Client** in the [Bukua Developer Dashboard](https://developer.bukuaplatform.com/).  
   - Obtain your:  
     - `client_id`  
     - `client_secret`  

## Configuration

1. Add the following to your `.env` file:

```env
BUKUA_ACCESS_CLIENT_ID=your-client-id
BUKUA_ACCESS_CLIENT_SECRET=your-client-secret
BUKUA_BASE_URL="https://bukua-core.apptempest.com/"
```

### Installation

1. In your terminal, run 

```bash
composer require digram/bukua-access
```

2. Clear your configuration cache by running

```bash
php artisan cache:clear
```

## Usage

### Counties

Get a paginated list of counties:

```php
use BukuaAccess\Facades\BukuaAccess;

try {
    $counties = BukuaAccess::counties(page: 1, per_page: 100);
    dd($counties);
} catch (\Exception $e) {
    // Handle error
}
```

### Subjects

Get a paginated list of subjects:

```php
use BukuaAccess\Facades\BukuaAccess;

try {
    $subjects = BukuaAccess::subjects(page: 1, per_page: 100);
    dd($subjects);
} catch (\Exception $e) {
    // Handle error
}
```

### Schools

Get a paginated list of schools:

```php
use BukuaAccess\Facades\BukuaAccess;

try {
    $schools = BukuaAccess::schools(page: 1, per_page: 100);
    dd($schools);
} catch (\Exception $e) {
    // Handle error
}
```

### Schools with Subjects

Get a paginated list of schools with subjects taught:

```php
use BukuaAccess\Facades\BukuaAccess;

try {
    $schoolsWithSubjects = BukuaAccess::schoolsWithSubjects(page: 1, per_page: 100);
    dd($schoolsWithSubjects);
} catch (\Exception $e) {
    // Handle error
}
```

### Schools with Subject Combinations

Get a paginated list of schools with subjects combinations:

```php
use BukuaAccess\Facades\BukuaAccess;

try {
    $schoolsWithSubjectCombinations = BukuaAccess::schoolsWithSubjectCombinations(page: 1, per_page: 100);
    dd($schoolsWithSubjectCombinations);
} catch (\Exception $e) {
    // Handle error
}
```

### Schools with Profiles

Get a paginated list of schools with profiles such as mission statement, fee structure, logo etc:

```php
use BukuaAccess\Facades\BukuaAccess;

try {
    $schoolsWithProfiles = BukuaAccess::schoolsWithProfiles(page: 1, per_page: 100);
    dd($schoolsWithProfiles);
} catch (\Exception $e) {
    // Handle error
}
```

### Schools with Departments

Get a paginated list of schools with departments:

```php
use BukuaAccess\Facades\BukuaAccess;

try {
    $schoolsWithDepartments = BukuaAccess::schoolsWithDepartments(page: 1, per_page: 100);
    dd($schoolsWithDepartments);
} catch (\Exception $e) {
    // Handle error
}
```