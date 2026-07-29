<?php

if (! function_exists('friendly_date')) {
    function friendly_date(?string $iso): string
    {
        if (! $iso) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($iso)->format('M j, Y \a\t H:i');
        } catch (\Throwable) {
            return $iso;
        }
    }
}
