<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxyController extends Controller
{
    private $targetBaseUrl ='https://hungtech.ct.ws/api'; // Target API base URL

    public function proxy(Request $request, $path = null)
    {
        try {
            // Get full path from request
            $fullPath = $path ?? $request->get('path', '');
            $targetUrl = rtrim($this->targetBaseUrl, '/') . '/' . ltrim($fullPath, '/');

            // Get method and data
            $method = $request->method();
            $headers = $this->getForwardHeaders($request);
            $data = $this->getRequestData($request);

            // Make HTTP request
            $httpClient = Http::withHeaders($headers)
                ->timeout(30)
                ->withoutVerifying(); // Skip SSL verification if needed

            $response = match(strtoupper($method)) {
                'GET' => $httpClient->get($targetUrl, $request->query()),
                'POST' => $httpClient->post($targetUrl, $data),
                'PUT' => $httpClient->put($targetUrl, $data),
                'PATCH' => $httpClient->patch($targetUrl, $data),
                'DELETE' => $httpClient->delete($targetUrl, $data),
                default => $httpClient->get($targetUrl)
            };

            // Log request if debug
            if (config('app.debug')) {
                Log::info("Proxy Request: {$method} {$targetUrl} - Status: {$response->status()}");
            }

            return response($response->body(), $response->status())
                ->withHeaders($this->getCorsHeaders())
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            Log::error("Proxy Error: " . $e->getMessage());

            return response()->json([
                'error' => 'Proxy Error',
                'message' => $e->getMessage(),
                'target_url' => $targetUrl ?? 'unknown'
            ], 500)->withHeaders($this->getCorsHeaders());
        }
    }

    public function options(Request $request)
    {
        return response('', 200)->withHeaders($this->getCorsHeaders());
    }

    private function getForwardHeaders(Request $request): array
    {
        $headers = [];
        $skipHeaders = ['host', 'connection', 'content-length', 'accept-encoding'];

        foreach ($request->headers->all() as $key => $values) {
            if (!in_array(strtolower($key), $skipHeaders)) {
                $headers[$key] = is_array($values) ? $values[0] : $values;
            }
        }

        // Ensure JSON content type
        if (!isset($headers['content-type']) && $request->isJson()) {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
    }

    private function getRequestData(Request $request)
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $request->isJson() ? $request->json()->all() : $request->all();
        }

        return [];
    }

    private function getCorsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '86400'
        ];
    }
}
