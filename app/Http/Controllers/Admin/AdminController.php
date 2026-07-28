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
        $log = array_reverse(YamlStore::all('log'));
        $feedback = array_reverse(YamlStore::all('feedback'));

        return view('admin.index', [
            'log' => array_slice($log, 0, 300),
            'feedback' => $feedback,
            'totalLogEntries' => count($log),
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
