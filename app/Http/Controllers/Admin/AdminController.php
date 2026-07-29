<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\YamlStore;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function index()
    {
        $rawLog = YamlStore::all('log');
        $feedback = array_reverse(YamlStore::all('feedback'));

        // group individual events into one row per visitor (session id)
        $bySession = [];
        foreach ($rawLog as $entry) {
            $sid = $entry['session'] ?? 'unknown';
            $bySession[$sid][] = $entry;
        }

        $visitors = [];
        foreach ($bySession as $sid => $events) {
            usort($events, fn ($a, $b) => ($a['time'] ?? '') <=> ($b['time'] ?? ''));

            $visitors[] = [
                'session' => $sid,
                'first_seen' => $events[0]['time'] ?? null,
                'last_seen' => end($events)['time'] ?? null,
                'ip' => collect($events)->pluck('ip')->filter()->first(),
                'referrer' => collect($events)->pluck('referrer')->filter()->first(),
                'count' => count($events),
                'events' => array_reverse($events),
            ];
        }
        usort($visitors, fn ($a, $b) => ($b['last_seen'] ?? '') <=> ($a['last_seen'] ?? ''));

        $eventCounts = [];
        foreach ($rawLog as $entry) {
            $type = $entry['type'] ?? 'unknown';
            $eventCounts[$type] = ($eventCounts[$type] ?? 0) + 1;
        }
        arsort($eventCounts);

        return view('admin.index', [
            'visitors' => array_slice($visitors, 0, 200),
            'totalVisitors' => count($visitors),
            'totalEvents' => count($rawLog),
            'eventCounts' => $eventCounts,
            'feedback' => $feedback,
        ]);
    }

    // feedback screenshots are stored on the private "local" disk (not
    // publicly linked), so admins view them through this authenticated route
    public function screenshot(string $path): StreamedResponse
    {
        abort_unless(str_starts_with($path, 'feedback-screenshots/'), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
