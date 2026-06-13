# Bukua Access — Developer Concepts & Onboarding Guide

> A deep-dive into why this package exists, how it's built, and what every moving part does.

---

## Table of Contents

1. [What Is Bukua Access?](#1-what-is-bukua-access)
2. [The Problem It Solves](#2-the-problem-it-solves)
3. [High-Level Architecture](#3-high-level-architecture)
4. [Authentication Model (OAuth2 Client Credentials)](#4-authentication-model-oauth2-client-credentials)
5. [Token Caching Strategy](#5-token-caching-strategy)
6. [Automatic Token-Refresh on 401](#6-automatic-token-refresh-on-401)
7. [The Permission / Scope System](#7-the-permission--scope-system)
8. [Available Data Domains](#8-available-data-domains)
9. [Package Internals — File-by-File](#9-package-internals--file-by-file)
10. [How the Facade Works](#10-how-the-facade-works)
11. [Configuration & Environment Variables](#11-configuration--environment-variables)
12. [Error Handling Contract](#12-error-handling-contract)
13. [Environments: Development vs. Production](#13-environments-development-vs-production)
14. [Extending the Package](#14-extending-the-package)
15. [Common Pitfalls & FAQ](#15-common-pitfalls--faq)

---

## 1. What Is Bukua Access?

`digram/bukua-access` is a **Laravel Composer package** that gives your application a clean, authenticated interface to the **Bukua Edtech Platform API**. Bukua is a Kenyan edtech platform that maintains a centralised registry of:

- Schools (with enriched data like profiles, departments, taught subjects, and subject combinations)
- Kenya's 47 counties
- Academic subjects
- TVET institutions
- The current academic year and term dates

Rather than every consumer application re-implementing OAuth flow, HTTP retries, and result normalisation against the Bukua REST API, this package encapsulates all of that into a handful of expressive method calls behind a simple **Laravel Facade**.

---

## 2. The Problem It Solves

Without this package, any application that needs Bukua data would have to:

1. Manually implement the OAuth2 **client credentials** flow to obtain a bearer token.
2. Store and invalidate that token themselves.
3. Re-try or handle `401 Unauthorized` responses when the cached token expires mid-session.
4. Know the exact API endpoint paths and query-parameter conventions.
5. Handle and normalise HTTP errors on every call site.

All of these cross-cutting concerns are handled once, centrally, inside this package. Every consuming application gets a three-line setup and a fluent API.

---

## 3. High-Level Architecture

```
Your Laravel App
       │
       │  BukuaAccess::schools(page: 1, per_page: 50)
       ▼
┌─────────────────────────────┐
│   BukuaAccess  (Facade)     │  ← src/Facades/BukuaAccess.php
└────────────┬────────────────┘
             │ resolves via IoC container ('bukuaaccess' binding)
             ▼
┌─────────────────────────────────────────────────────┐
│          BukuaAccessController                       │  ← src/Controllers/
│                                                     │
│  uses AuthenticatesWithToken ──► Cache + HTTP client│
│  uses Counties                                      │
│  uses Subjects                                      │
│  uses Schools                                       │
│  uses Tvets                                         │
│  uses General                                       │
└──────────────────────────┬──────────────────────────┘
                           │  HTTPS REST calls
                           ▼
              ┌────────────────────────┐
              │   Bukua Platform API   │
              │  (dev or prod base URL)│
              └────────────────────────┘
```

The **Facade** is the only surface a consumer application ever touches. The controller and traits are internal implementation details.

---

## 4. Authentication Model (OAuth2 Client Credentials)

The Bukua API uses the **OAuth 2.0 Client Credentials Grant**, the standard machine-to-machine authentication flow. There is no user login or redirect involved.

### How it works

1. Your application presents a `client_id` + `client_secret` pair to the Bukua token endpoint:

   ```
   POST {BASE_URL}/api/v1/bukua-auth/client-token
   Content-Type: application/x-www-form-urlencoded

   client_id=<your-id>&client_secret=<your-secret>
   ```

2. Bukua responds with a JSON body:

   ```json
   {
     "access_token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
     "token_type": "Bearer",
     ...
   }
   ```

3. All subsequent requests carry this token in the `Authorization: Bearer <token>` header.

The credentials are registered in the **Bukua Developer Dashboard** when you create a _Core Access App_. Each app can be granted a specific set of **permissions (scopes)** that control which endpoints it can reach.

**Relevant code:** `src/Traits/AuthenticatesWithToken.php` → `getToken()`

---

## 5. Token Caching Strategy

Obtaining a token on every single API call would be wasteful and slow. The package caches the access token in your application's configured **Laravel Cache** store.

| Detail       | Value                                                               |
| ------------ | ------------------------------------------------------------------- |
| Cache key    | `bukua_access_token`                                                |
| Default TTL  | `3600 * 24 * 365` seconds (~1 year)                                 |
| Cache driver | Whatever your app's default cache driver is (`file`, `redis`, etc.) |

```php
// AuthenticatesWithToken.php (simplified)
Cache::remember($this->tokenCacheKey, $this->tokenCacheTtl, function () {
    // Only runs if the key is absent from the cache
    $response = Http::asForm()->post('.../client-token', [...credentials...]);
    return $response->throw()->json('access_token');
});
```

> **Why such a long TTL?**
> Bukua's client credential tokens are long-lived application tokens (not user session tokens). The cache TTL is set to be effectively permanent so the token persists across deployments and server restarts. The cache is **proactively invalidated** if a `401` response is received (see §6).

---

## 6. Automatic Token-Refresh on 401

Because the cached token might be stale (e.g., revoked by the platform, or the cache cleared externally), the `makeAuthenticatedRequest()` method implements a single **retry-on-401** strategy:

```
Request attempt 0
  ├─ If 401 received:
  │    1. Forget the cached token  (Cache::forget)
  │    2. Increment attempt counter
  │    3. Loop: getToken() fetches a fresh token and retries once
  └─ If any other error: throw RuntimeException immediately

Request attempt 1 (retry)
  ├─ If still 401: throw RuntimeException (no further retries)
  └─ If success: return decoded JSON
```

This means consumer code **never needs to manage token lifecycle** — it is completely transparent.

---

## 7. The Permission / Scope System

When you create a **Core Access App** on the Bukua developer portal, you select which permissions (scopes) to grant it. The package's methods map to these permissions as follows:

| Method                                          | Required Permission  |
| ----------------------------------------------- | -------------------- |
| `BukuaAccess::counties()`                       | `county:view`        |
| `BukuaAccess::subjects()`                       | `subject:view`       |
| `BukuaAccess::tvets()`                          | `tvet:view`          |
| `BukuaAccess::schools()`                        | `school:view`        |
| `BukuaAccess::schoolsWithSubjects()`            | `school:view`        |
| `BukuaAccess::schoolsWithSubjectCombinations()` | `school:view`        |
| `BukuaAccess::schoolsWithProfiles()`            | `school:view`        |
| `BukuaAccess::schoolsWithDepartments()`         | `school:view`        |
| `BukuaAccess::updateSchoolInfo()`               | `school_info:update` |
| `BukuaAccess::academicYear()`                   | _(none required)_    |

If your app lacks a required permission, the Bukua API will return a `403 Forbidden` (or similar), and the package will surface that as a `RuntimeException`.

---

## 8. Available Data Domains

### 8.1 Counties

Represents Kenya's 47 administrative counties.

```php
BukuaAccess::counties(page: 1, per_page: 47);
```

Returns a paginated JSON response. You can use `per_page: 47` to fetch all counties in one request.

---

### 8.2 Subjects

Academic subjects taught in Kenyan schools.

```php
BukuaAccess::subjects(page: 1, per_page: 100);
```

---

### 8.3 TVETs

Technical and Vocational Education and Training (TVET) institutions registered on the Bukua platform.

```php
BukuaAccess::tvets(page: 1, per_page: 100);
```

---

### 8.4 Schools

Schools are the core data entity in the Bukua platform. There are several enriched variants:

| Method                             | What it includes                                                  |
| ---------------------------------- | ----------------------------------------------------------------- |
| `schools()`                        | Base school record (name, uid, county, etc.)                      |
| `schoolsWithSubjects()`            | Base + flat list of subjects taught                               |
| `schoolsWithSubjectCombinations()` | Base + subject combinations (e.g., Arts, Sciences)                |
| `schoolsWithProfiles()`            | Base + institutional profile (mission, fee structure, logo, etc.) |
| `schoolsWithDepartments()`         | Base + departments/faculties                                      |

All school-listing methods are paginated:

```php
BukuaAccess::schools(page: 1, per_page: 100);
```

#### Updating a School Record

`updateSchoolInfo()` uses an HTTP `PUT` request and requires the `school_info:update` permission:

```php
BukuaAccess::updateSchoolInfo(
    school_uid: 'efd8cccf-861f-4392-8e77-6a08b056e65e', // UUID
    data: [
        'clean_name'       => 'Jitahidi Senior School',
        'short_name'       => 'Jitahidi',
        'abbreviation'     => 'JSS',
        'domain'           => 'jitahidischool',
        'national_code'    => 'ABC123',
        'year_established' => 2000,
    ]
);
```

Only the fields you include in `data` will be updated (a partial update). All fields are optional.

---

### 8.5 Academic Year

Returns the current Kenyan academic year and its term date ranges. This endpoint requires **no** permissions.

```php
BukuaAccess::academicYear();
```

---

## 9. Package Internals — File-by-File

```
src/
├── config/
│   └── bukua-access.php               # Default config values (merged into 'services')
├── Controllers/
│   └── BukuaAccessController.php      # The concrete class behind the Facade
├── Facades/
│   └── BukuaAccess.php                # The static Facade entry point
├── Providers/
│   ├── BukuaAccessServiceProvider.php       # Registers the singleton + merges config
│   └── BukuaAccessEventServiceProvider.php  # Placeholder for future event hooks
└── Traits/
    ├── AuthenticatesWithToken.php      # Token fetch, cache, retry logic
    ├── Counties.php                    # counties()
    ├── Subjects.php                    # subjects()
    ├── Tvets.php                       # tvets()
    ├── Schools.php                     # schools(), schoolsWithSubjects(), etc.
    └── General.php                     # academicYear()
```

### `BukuaAccessController`

The controller is not used for routing. It exists solely as the **concrete class** that the IoC container instantiates for the Facade. It uses PHP **trait composition** to mix in all the data-access methods:

```php
class BukuaAccessController extends Controller
{
    use AuthenticatesWithToken; // core HTTP + token logic
    use Counties;               // county methods
    use Subjects;               // subject methods
    use Tvets;                  // tvet methods
    use Schools;                // school methods
    use General;                // misc methods (academicYear)

    protected string $baseUrl;
    protected string $tokenCacheKey = 'bukua_access_token';
    protected int    $tokenCacheTtl = 3600 * 24 * 365;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.bukua_access.base_url'), '/');
    }
}
```

Adding a new **data domain** means adding a new `Trait` file and `use`-ing it here.

### `BukuaAccessServiceProvider`

Registered automatically via Laravel's package auto-discovery (declared in `composer.json` under `extra.laravel.providers`). It does two things:

1. **`boot()`** — Merges the package's `config/bukua-access.php` defaults into the `services` config key, so `config('services.bukua_access.*')` always works.
2. **`register()`** — Binds the `BukuaAccessController` as a **singleton** under the `bukuaaccess` key in the IoC container.

### `BukuaAccessEventServiceProvider`

A scaffolded but currently empty event service provider. It is reserved for future use — for example, dispatching an event when a token is refreshed, or when an API call fails.

---

## 10. How the Facade Works

Laravel Facades provide a static interface to objects resolved from the IoC container. The full chain is:

```
BukuaAccess::schools(...)
     │
     │  __callStatic()  [Facade base class]
     ▼
Container::make('bukuaaccess')   ← key declared in BukuaAccess::getFacadeAccessor()
     │
     ▼
singleton BukuaAccessController instance
     │
     ▼
BukuaAccessController::schools(...)  [inherited from Schools trait]
```

Because it is a **singleton**, the controller is only instantiated once per request lifecycle. The singleton also means that the cached token is acquired at most once per request, regardless of how many API calls are made.

---

## 11. Configuration & Environment Variables

The package reads three environment variables:

| Variable                          | Description                    | Default                             |
| --------------------------------- | ------------------------------ | ----------------------------------- |
| `BUKUA_CORE_ACCESS_CLIENT_ID`     | Your app's OAuth client ID     | _(none)_                            |
| `BUKUA_CORE_ACCESS_CLIENT_SECRET` | Your app's OAuth client secret | _(none)_                            |
| `BUKUA_BASE_URL`                  | The Bukua API base URL         | `https://bukua-core.apptempest.com` |

These are merged into the `services` config key in Laravel, making them accessible at:

```php
config('services.bukua_access.client_id')
config('services.bukua_access.client_secret')
config('services.bukua_access.base_url')
```

You do **not** need to publish the config file — it is automatically merged on boot.

---

## 12. Error Handling Contract

Every public method in the package can throw a `\RuntimeException`. The two scenarios are:

| Scenario                                                   | Behaviour                                                              |
| ---------------------------------------------------------- | ---------------------------------------------------------------------- |
| Token cannot be retrieved (network error, bad credentials) | Throws `RuntimeException('Unable to retrieve access token')`           |
| API call fails (non-401 HTTP error, e.g. 403, 500)         | Throws `RuntimeException("Failed to fetch data from {endpoint}: ...")` |
| 401 and retry also fails                                   | Throws `RuntimeException('Unable to authenticate after retry')`        |

**Always wrap calls in a try/catch:**

```php
try {
    $schools = BukuaAccess::schools(page: 1, per_page: 100);
} catch (\RuntimeException $e) {
    // Log, return fallback, or re-throw as a domain exception
    Log::error('Bukua API error: ' . $e->getMessage());
}
```

The internal `Http::...->throw()` calls ensure that any non-2xx HTTP response from the Bukua API is immediately surfaced rather than silently returning `null`.

---

## 13. Environments: Development vs. Production

The Bukua Platform has two completely separate environments with distinct base URLs and distinct credential sets.

| Environment     | Base URL                            | Purpose                   |
| --------------- | ----------------------------------- | ------------------------- |
| **Development** | `https://bukua-core.apptempest.com` | Testing, integration work |
| **Production**  | `https://app.bukuaplatform.com`     | Live data                 |

Set the appropriate URL via `BUKUA_BASE_URL` in your `.env`. Your `client_id` and `client_secret` from development **will not** work in production — you must register a separate Core Access App in each environment.

> **Important:** After changing `.env` values on a server with a config cache, always run:
>
> ```bash
> php artisan config:cache   # production
> # or
> php artisan config:clear   # development
> ```

---

## 14. Extending the Package

The trait-based design makes adding new API endpoints straightforward.

### Adding a new data domain (e.g., Teachers)

1. **Create the trait** `src/Traits/Teachers.php`:

   ```php
   <?php

   namespace BukuaAccess\Traits;

   trait Teachers
   {
       public function teachers(int $page, int $per_page)
       {
           return $this->makeAuthenticatedRequest('/api/v1/teachers', [
               'page'     => $page,
               'per_page' => $per_page,
           ]);
       }
   }
   ```

2. **Mix it in** to `BukuaAccessController`:

   ```php
   use BukuaAccess\Traits\Teachers;

   class BukuaAccessController extends Controller
   {
       use AuthenticatesWithToken;
       use Counties;
       use Subjects;
       use Schools;
       use General;
       use Teachers; // ← add here
       ...
   }
   ```

3. **Document the required permission** in the README and in this file (§7 table).

That's it — the Facade automatically exposes the new method.

### Supported HTTP Methods

`makeAuthenticatedRequest()` accepts a `$method` parameter that defaults to `'get'`. Pass `'post'`, `'put'`, `'patch'`, or `'delete'` for write operations, as demonstrated by `updateSchoolInfo()`:

```php
$this->makeAuthenticatedRequest(
    endpoint: '/api/v1/some/endpoint',
    data:     ['key' => 'value'],
    method:   'post'
);
```

---

## 15. Common Pitfalls & FAQ

### Q: I'm getting "Unable to retrieve access token" immediately after setup.

**Causes & fixes:**

- `BUKUA_CORE_ACCESS_CLIENT_ID` or `BUKUA_CORE_ACCESS_CLIENT_SECRET` is missing or incorrect in `.env`.
- You are pointing at the wrong environment URL (e.g., using production credentials against the dev URL).
- You forgot to run `php artisan config:clear` after editing `.env`.

---

### Q: My requests are failing with `403 Forbidden`.

Your app's _Core Access App_ on the Bukua platform does not have the required permission for the endpoint you are calling. Log into the Bukua Developer Dashboard and grant the appropriate permission (e.g., `school:view`) to your app.

---

### Q: I'm receiving stale data in development.

The token is cached and data calls are not — but if you've recently had your app's permissions changed on the Bukua side, invalidate the cached token manually:

```bash
php artisan cache:forget bukua_access_token
```

---

### Q: How do I paginate through all records?

The API uses page-based pagination. Iterate until the response signals that there are no more pages:

```php
$page = 1;
$allSchools = [];

do {
    $response = BukuaAccess::schools(page: $page, per_page: 100);
    $allSchools = array_merge($allSchools, $response['data'] ?? []);
    $page++;
    $hasMore = !empty($response['next_page_url']); // adjust to actual response shape
} while ($hasMore);
```

Check the actual Bukua API response structure to confirm the exact pagination metadata keys.

---

### Q: Can I use this package outside of Laravel?

No. The package relies on `Illuminate\Support\Facades\Cache`, `Illuminate\Support\Facades\Http`, and the Laravel IoC container. It is designed exclusively for Laravel 8+.

---

### Q: Does the package handle rate limiting?

Not currently. If you receive a `429 Too Many Requests` response, a `RuntimeException` will be thrown. Implementing rate-limit back-off (e.g., using `sleep()` and a retry loop in the consumer, or a queue-based approach) is left to the consuming application.

---

### Q: What happens if the Bukua API is down?

`Http::...->throw()` will surface the HTTP error as a `RequestException`, which the package wraps in a `RuntimeException`. The token cache is not cleared for non-401 errors, so the next attempt will still use the previously cached token.

---

_Last updated: March 2026_
