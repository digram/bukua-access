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

### Fetching Counties

Get a paginated list of counties:

**Parameters:**
- `$page`: Page number (starting from 1)
- `$per_page`: Number of items per page

```php
use BukuaAccess\Facades\BukuaAccess;

try {
    $counties = BukuaAccess::counties(page: 1, per_page: 10);
    dd($counties);
} catch (\Exception $e) {
    // Handle error
}
```

### Fetching Subjects

Get a paginated list of subjects:

**Parameters:**
- `$page`: Page number (starting from 1)
- `$per_page`: Number of items per page

```php
use BukuaAccess\Facades\BukuaAccess;

try {
    $subjects = BukuaAccess::subjects(page: 1, per_page: 10);
    dd($subjects);
} catch (\Exception $e) {
    // Handle error
}
```