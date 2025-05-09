<?php

namespace BukuaAccess\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\RequestException;

class BukuaAccessController extends Controller
{
    protected string $baseUrl;
    protected string $tokenCacheKey = 'bukua_access_token';
    protected int $tokenCacheTtl = 3600 * 24 * 365;

    public function __construct()
    {
        $this->baseUrl = config('services.bukua_access.base_url');
    }

    private function getToken(): ?string
    {
        return Cache::remember($this->tokenCacheKey, $this->tokenCacheTtl, function () {
            $response = Http::asForm()->post($this->baseUrl . 'api/v1/bukua-auth/client-token', [
                'client_id'     => config('services.bukua_access.client_id'),
                'client_secret' => config('services.bukua_access.client_secret'),
            ]);

            return $response->throw()->json('access_token');
        });
    }

    private function makeAuthenticatedRequest(string $endpoint, array $queryParams = [])
    {
        $maxRetries = 1; // only retry once after 401
        $attempt = 0;

        while ($attempt <= $maxRetries) {
            $token = $this->getToken();

            if (!$token) {
                throw new \RuntimeException('Unable to retrieve access token');
            }

            try {
                return Http::withToken($token)
                    ->get($this->baseUrl . $endpoint, $queryParams)
                    ->throw()
                    ->json();
            } catch (RequestException $e) {
                if ($e->response && $e->response->status() === 401 && $attempt < $maxRetries) {
                    Cache::forget($this->tokenCacheKey);
                    $attempt++;
                    continue;
                }

                throw new \RuntimeException(
                    "Failed to fetch data from {$endpoint}: " . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }

        throw new \RuntimeException('Unable to authenticate after retry');
    }

    public function counties(int $page, int $per_page)
    {
        $data = $this->makeAuthenticatedRequest('api/v1/counties', [
            'page' => $page,
            'per_page' => $per_page,
        ]);

        return response()->json($data);
    }

    public function subjects(int $page, int $per_page)
    {
        $data = $this->makeAuthenticatedRequest('api/v1/subjects', [
            'page' => $page,
            'per_page' => $per_page,
        ]);

        return response()->json($data);
    }
}
