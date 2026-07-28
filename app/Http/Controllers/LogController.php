<?php

namespace App\Http\Controllers;

use App\Support\YamlStore;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /** Event types the frontend is allowed to record. */
    private const ALLOWED_TYPES = [
        'pageview', 'upload_horse', 'upload_pad', 'crop_used',
        'transform_used', 'fullscreen_toggled', 'export', 'session_end',
    ];

    public function store(Request $request)
    {
        $data = $request->isJson() ? $request->json()->all() : json_decode($request->getContent(), true);
        $type = $data['type'] ?? null;

        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            return response()->noContent();
        }

        YamlStore::append('log', [
            'time' => now()->toIso8601String(),
            'type' => $type,
            'session' => (string) $request->session()->getId(),
            'ip' => $this->truncateIp($request->ip()),
            'referrer' => $data['referrer'] ?? $request->headers->get('referer'),
            'meta' => $data['meta'] ?? null,
        ]);

        return response()->noContent();
    }

    /** DSGVO-friendly IP: drop the last octet (v4) / last group (v6). */
    private function truncateIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }
        if (str_contains($ip, '.')) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        if (str_contains($ip, ':')) {
            return preg_replace('/:[0-9a-f]*$/i', ':0', $ip);
        }

        return $ip;
    }
}
