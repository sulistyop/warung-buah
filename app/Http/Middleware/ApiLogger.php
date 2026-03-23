<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $requestData = [
            'method'  => $request->method(),
            'url'     => $request->fullUrl(),
            'ip'      => $request->ip(),
            'user_id' => optional($request->user())->id,
            'body'    => $this->filterSensitive($request->except(['password', 'password_confirmation', 'token'])),
            'headers' => $this->filterHeaders($request->headers->all()),
        ];

        // Log::channel('api')->info('API Request', $requestData);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $responseBody = json_decode($response->getContent(), true);

        $logData = [
            'method'      => $request->method(),
            'url'         => $request->fullUrl(),
            'status'      => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id'     => optional($request->user())->id,
            'response'    => $responseBody,
        ];

        // if ($response->getStatusCode() >= 500) {
        //     Log::channel('api')->error('API Response Error', $logData);
        // } elseif ($response->getStatusCode() >= 400) {
        //     Log::channel('api')->warning('API Response Warning', $logData);
        // } else {
        //     Log::channel('api')->info('API Response', $logData);
        // }

        return $response;
    }

    private function filterSensitive(array $data): array
    {
        $sensitive = ['password', 'token', 'secret', 'key', 'authorization'];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitive)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }

    private function filterHeaders(array $headers): array
    {
        $allowed = ['content-type', 'accept', 'user-agent'];
        return array_intersect_key($headers, array_flip($allowed));
    }
}
