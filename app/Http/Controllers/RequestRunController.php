<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RequestRunController extends Controller
{
    /**
     * Execute an arbitrary HTTP request on behalf of the client and
     * return the response so the frontend can render it. Powers both
     * the Quick Test tab and per-endpoint "Send" buttons.
     */
    public function run(Request $request)
    {
        $data = $request->validate([
            'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
            'url' => 'required|string',
            'body_type' => 'nullable|string|in:json,form',
            'body' => 'nullable|string',
            'params' => 'nullable|array',
            'headers' => 'nullable|array',
            'headers.*.key' => 'nullable|string',
            'headers.*.value' => 'nullable|string',
        ]);

        $url = $data['url'];
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'http://'.$url;
        }

        $headers = [];
        foreach ($data['headers'] ?? [] as $header) {
            if (! empty($header['key'])) {
                $headers[$header['key']] = $header['value'] ?? '';
            }
        }

        $method = $data['method'];
        $bodyType = $data['body_type'] ?? 'json';

        $pending = Http::withHeaders($headers)
            ->timeout(15)
            ->withOptions(['verify' => true]);

        $options = [];

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            if ($bodyType === 'form') {
                $pending = $pending->asForm();
                $options = $data['params'] ?? [];
            } else {
                $pending = $pending->asJson();
                $raw = $data['body'] ?? '';
                if (trim($raw) === '') {
                    $options = [];
                } else {
                    $decoded = json_decode($raw, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return response()->json([
                            'error' => 'Invalid JSON body: '.json_last_error_msg(),
                        ], 422);
                    }
                    $options = $decoded;
                }
            }
        } else {
            // GET / DELETE: send params as query string.
            $options = $data['params'] ?? [];
        }

        $start = microtime(true);

        try {
            $response = $pending->send($method, $url, $method === 'GET' || $method === 'DELETE'
                ? ['query' => $options]
                : ['json' => $options]
            );
        } catch (ConnectionException $e) {
            return response()->json([
                'error' => 'Connection failed: '.$e->getMessage(),
            ], 502);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Request failed: '.$e->getMessage(),
            ], 500);
        }

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $contentType = $response->header('Content-Type');
        $rawBody = $response->body();
        $jsonBody = null;
        if ($contentType && str_contains($contentType, 'json')) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $jsonBody = $decoded;
            }
        }

        return response()->json([
            'status' => $response->status(),
            'ok' => $response->successful(),
            'duration_ms' => $durationMs,
            'headers' => $response->headers(),
            'body' => $jsonBody,
            'raw_body' => $rawBody,
            'is_json' => $jsonBody !== null,
        ]);
    }
}
