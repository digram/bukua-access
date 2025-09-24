# Bukua Edtech API Services for Laravel

A Laravel package for integrating with Bukua Edtech API services, providing easy access to schools data.

## Features

- Authentication with Bukua API using client credentials
- Simple methods to fetch paginated schools data

## Prerequisites  

Before using this package, ensure you have:

1. **Bukua Developer Account**
   - Register as an app developer at [Bukua Platform - Development Environment](https://bukua-core.apptempest.com/login) or [Bukua Platform - Production Environment](https://app.bukuaplatform.com/login)
   - Create a **Core Access App** in the selected environment above

2. **Application Credentials**
   - Obtain your `client_id` and `client_secret` from the Bukua Developer Dashboard

3. **Laravel Application**
   - Laravel 8.x or higher
   - Composer for dependency management

## Configuration

1. Add the following to your `.env` file:

```env
BUKUA_CORE_ACCESS_CLIENT_ID=your-client-id
BUKUA_CORE_ACCESS_CLIENT_SECRET=your-client-secret
BUKUA_BASE_URL="https://bukua-core.apptempest.com"  # Development
# BUKUA_BASE_URL="https://app.bukuaplatform.com"    # Production
```

### Installation

1. In your terminal, run 

```bash
composer require digram/bukua-access
```

2. Clear your configuration cache by running

   ```bash
   # For development
   php artisan config:clear && php artisan route:clear

   # For production
   php artisan config:cache && php artisan route:cache
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