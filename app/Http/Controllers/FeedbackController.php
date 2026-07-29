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
            'email' => ['nullable', 'email', 'max:255'],
            'screenshot' => ['nullable', 'image', 'max:5120'],
        ]);

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('feedback-screenshots', 'local');
        }

        YamlStore::append('feedback', [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'time' => now()->toIso8601String(),
            'category' => $validated['category'] ?? 'other',
            'text' => $validated['text'],
            'email' => $validated['email'] ?? null,
            'screenshot' => $screenshotPath,
            'session' => (string) $request->session()->getId(),
        ]);

        return response()->json(['ok' => true]);
    }
}
