<?php
declare(strict_types=1);
namespace LoveFortune\Core\Support;

/** Non-executable private data; POSIX permissions match the deployment contract. */
final class PrivateJsonConfig
{
    public const MAX_BYTES = 65536;

    public function load(#[\SensitiveParameter] string $path): array
    {
        $handle = null;
        try {
            if ($path === '' || !str_starts_with($path, '/') || str_contains($path, '://')
                || str_contains($path, "\0") || str_contains($path, '\\') || str_contains($path, '//')
                || preg_match('~(?:^|/)\.{1,2}(?:/|$)~', $path)) { throw new RuntimeConfigException(); }
            $part = '';
            foreach (explode('/', trim($path, '/')) as $segment) {
                $part .= '/' . $segment;
                clearstatcache(true, $part);
                if (is_link($part)) { throw new RuntimeConfigException(); }
            }
            foreach ([defined('ABSPATH') ? (string)constant('ABSPATH') : '', (string)($_SERVER['DOCUMENT_ROOT'] ?? '')] as $public) {
                $root = $public === '' ? false : realpath($public);
                if ($root !== false && ($path === $root || str_starts_with($path, rtrim($root, '/') . '/'))) { throw new RuntimeConfigException(); }
            }
            $parent = @lstat(dirname($path));
            $before = @lstat($path);
            if ($parent === false || ($parent['mode'] & 0777) !== 0700 || $before === false
                || ($before['mode'] & 0170000) !== 0100000 || ($before['mode'] & 0777) !== 0600
                || $before['nlink'] !== 1 || $before['uid'] !== $parent['uid']
                || $before['size'] > self::MAX_BYTES || !is_readable($path)) { throw new RuntimeConfigException(); }
            $handle = @fopen($path, 'rb');
            if ($handle === false) { throw new RuntimeConfigException(); }
            $opened = fstat($handle);
            $text = stream_get_contents($handle, self::MAX_BYTES + 1);
            clearstatcache(true, $path);
            $after = @lstat($path);
            $finished = fstat($handle);
            foreach ([$opened, $after, $finished] as $stat) {
                if ($stat === false) { throw new RuntimeConfigException(); }
                foreach (['dev', 'ino', 'mode', 'uid', 'nlink', 'size', 'mtime', 'ctime'] as $field) {
                    if ($stat[$field] !== $before[$field]) { throw new RuntimeConfigException(); }
                }
            }
            if (!is_string($text) || strlen($text) !== $before['size'] || strlen($text) > self::MAX_BYTES) { throw new RuntimeConfigException(); }
            return $this->parse($text);
        } catch (\Throwable) {
            throw new RuntimeConfigException();
        } finally {
            if (is_resource($handle)) { fclose($handle); }
        }
    }

    private function parse(#[\SensitiveParameter] string $text): array
    {
        // Validate JSON first, then scan its flat string pairs without losing duplicate keys.
        $object = json_decode($text, false, 4, JSON_THROW_ON_ERROR);
        if (!$object instanceof \stdClass) { throw new RuntimeConfigException(); }
        $offset = 0;
        $take = static function (string $pattern) use ($text, &$offset): string {
            if (preg_match($pattern, $text, $match, 0, $offset) !== 1) { throw new RuntimeConfigException(); }
            $offset += strlen($match[0]);
            return $match[0];
        };
        $take('/\G\s*\{\s*/');
        $values = [];
        if (preg_match('/\G\}\s*\z/', $text, $m, 0, $offset) === 1) { return []; }
        $string = '/\G"(?:[^"\\\\\x00-\x1f]|\\\\(?:["\\\\\/bfnrt]|u[0-9a-fA-F]{4}))*"/';
        while (true) {
            $key = json_decode($take($string), true, 2, JSON_THROW_ON_ERROR);
            $take('/\G\s*:\s*/');
            $value = json_decode($take($string), true, 2, JSON_THROW_ON_ERROR);
            if (!in_array($key, RuntimeConfig::KEYS, true) || array_key_exists($key, $values)) { throw new RuntimeConfigException(); }
            $values[$key] = $value;
            if (preg_match('/\G\s*\}\s*\z/', $text, $m, 0, $offset) === 1) { return $values; }
            $take('/\G\s*,\s*/');
        }
    }
}
