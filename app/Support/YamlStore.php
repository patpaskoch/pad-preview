<?php

namespace App\Support;

use Symfony\Component\Yaml\Yaml;

/**
 * Minimal flat-file "database": each named store is a single YAML file of
 * list entries under storage/app/data/. Good enough for this app's low
 * write volume (visit/feedback logs); append() takes an exclusive file
 * lock for the read-modify-write cycle so concurrent requests can't clobber
 * each other.
 */
class YamlStore
{
    protected static function path(string $name): string
    {
        $dir = storage_path('app/data');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir.'/'.$name.'.yaml';
    }

    public static function all(string $name): array
    {
        $path = self::path($name);
        if (! file_exists($path)) {
            return [];
        }

        $data = Yaml::parseFile($path);

        return is_array($data) ? $data : [];
    }

    public static function append(string $name, array $entry): void
    {
        $path = self::path($name);
        $handle = fopen($path, 'c+');
        flock($handle, LOCK_EX);

        $contents = stream_get_contents($handle);
        $entries = $contents ? (Yaml::parse($contents) ?: []) : [];
        $entries[] = $entry;

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, Yaml::dump($entries, 4, 2));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
