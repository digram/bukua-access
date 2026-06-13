# Bukua Access — Mermaid Diagrams

Mermaid.js translations of every diagram found in [`concepts_onboarding.md`](./concepts_onboarding.md).

---

## Diagram 1 — High-Level Architecture (§3)

> How a call flows from your application through the Facade, into the controller, and out to the Bukua REST API.

```mermaid
flowchart TD
    App["Your Laravel App\n(e.g. BukuaAccess::schools(page: 1, per_page: 50))"]

    Facade["BukuaAccess Facade\nsrc/Facades/BukuaAccess.php"]

    Controller["BukuaAccessController\nsrc/Controllers/BukuaAccessController.php\n\nuses AuthenticatesWithToken → Cache + HTTP client\nuses Counties\nuses Subjects\nuses Tvets\nuses Schools\nuses General"]

    API["Bukua Platform API\n(dev or prod base URL)"]

    App -->|"static call"| Facade
    Facade -->|"resolves via IoC container\n('bukuaaccess' binding)"| Controller
    Controller -->|"HTTPS REST calls"| API
```

---

## Diagram 2 — Automatic Token-Refresh on 401 (§6)

> The single-retry loop inside `makeAuthenticatedRequest()` that transparently handles a stale cached token.

```mermaid
flowchart TD
    Start(["makeAuthenticatedRequest()"])

    GetToken["getToken()\nfetch token from cache\nor request a new one"]

    SendReq["Send HTTP request\nwith Bearer token"]

    Is401{"Response\n401?"}

    OtherErr{"Other\nHTTP error?"}

    IsRetry{"Already\nretried?"}

    ForgetToken["Cache::forget()\nInvalidate cached token"]

    Retry["Increment attempt\nLoop back to getToken()"]

    ThrowAuth(["throw RuntimeException\n'Unable to authenticate after retry'"])

    ThrowErr(["throw RuntimeException\n'Failed to fetch data from {endpoint}'"])

    ThrowNoToken(["throw RuntimeException\n'Unable to retrieve access token'"])

    Return(["Return decoded JSON ✓"])

    Start --> GetToken
    GetToken -->|"token is null"| ThrowNoToken
    GetToken -->|"token obtained"| SendReq
    SendReq --> Is401
    Is401 -->|"No"| OtherErr
    Is401 -->|"Yes"| IsRetry
    IsRetry -->|"Yes — max retries reached"| ThrowAuth
    IsRetry -->|"No"| ForgetToken
    ForgetToken --> Retry
    Retry --> GetToken
    OtherErr -->|"Yes"| ThrowErr
    OtherErr -->|"No — 2xx success"| Return
```

---

## Diagram 3 — Facade Resolution Chain (§10)

> How a static Facade call resolves through the Laravel IoC container to the concrete singleton instance.

```mermaid
flowchart TD
    StaticCall["BukuaAccess::schools(...)"]

    CallStatic["Facade::__callStatic()\n(Laravel Facade base class)"]

    Container["Container::make('bukuaaccess')\nkey returned by getFacadeAccessor()"]

    Singleton["Singleton: BukuaAccessController\n(instantiated once per request lifecycle)"]

    Method["BukuaAccessController::schools(...)\ninherited from Schools trait"]

    StaticCall --> CallStatic
    CallStatic --> Container
    Container --> Singleton
    Singleton --> Method
```
