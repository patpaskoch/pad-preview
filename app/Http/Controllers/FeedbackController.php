<?php

namespace App\Http\Controllers;

use App\Support\YamlStore;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:4000'],
            'category' => ['nullable', 'string', 'in:bug,idea,other'],
            'screenshot' => ['nullable', 'image', 'max:5120'],
        ]);

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('feedback-screenshots', 'local');
        }

        YamlStore::append('feedback', [
            'time' => now()->toIso8601String(),
            'category' => $validated['category'] ?? 'other',
            'text' => $validated['text'],
            'screenshot' => $screenshotPath,
            'session' => (string) $request->session()->getId(),
        ]);

        return response()->json(['ok' => true]);
    }
}
