<?php

namespace App\Support;

class LogReader
{
    /**
     * List log files under storage/logs ordered by modified time desc.
     * @return array<int, array{basename:string,path:string,size:int,mtime:int}>
     */
    public static function listFiles(): array
    {
        $dir = storage_path('logs');
        $files = glob($dir . '/*.log');
        $out = [];
        foreach ($files as $path) {
            $out[] = [
                'basename' => basename($path),
                'path' => $path,
                'size' => @filesize($path) ?: 0,
                'mtime' => @filemtime($path) ?: 0,
            ];
        }
        usort($out, function ($a, $b) {
            return ($b['mtime'] <=> $a['mtime']) ?: ($b['size'] <=> $a['size']);
        });
        return $out;
    }

    /**
     * Read the last N bytes of a file safely.
     */
    public static function tail(string $path, int $bytes = 524288): string
    {
        if (!is_file($path) || !is_readable($path)) {
            return '';
        }
        $size = filesize($path);
        if ($size === 0) {
            return '';
        }
        $offset = max(0, $size - $bytes);
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            return '';
        }
        try {
            if ($offset > 0) {
                fseek($fh, $offset);
            }
            $data = stream_get_contents($fh) ?: '';
        } finally {
            fclose($fh);
        }
        // Ensure we start at a new line boundary to not break parsing
        $pos = strpos($data, "\n");
        if ($pos !== false) {
            $data = substr($data, $pos + 1);
        }
        return $data;
    }

    /**
     * Parse Laravel-style log text into entries.
     * Returns array of entries: ts, level, message, context, stack, raw
     * @return array<int, array<string, mixed>>
     */
    public static function parseEntries(string $raw): array
    {
        if ($raw === '') return [];
        $lines = preg_split('/\r?\n/', $raw);
        $entries = [];
        $current = null;
        foreach ($lines as $line) {
            if ($line === '') continue;
            if (preg_match('/^\[(?<ts>[^\]]+)\]\s+[^.]+\.(?<level>[A-Z]+):\s(?<rest>.*)$/', $line, $m)) {
                // Start of a new entry
                if ($current) {
                    $entries[] = self::maskEntry($current);
                }
                $msg = $m['rest'];
                $context = null;
                $stack = [];
                // Try to split JSON context at the end: ... {..} [...]
                if (preg_match('/^(?<msg>.*?)(?<json>\{.*\})\s*(?<extra>\[.*\])?\s*$/', $msg, $mm)) {
                    $msg = trim($mm['msg']);
                    $json = $mm['json'] ?? null;
                    if ($json) {
                        $decoded = json_decode($json, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $context = $decoded;
                        }
                    }
                }
                $current = [
                    'ts' => $m['ts'],
                    'level' => strtolower($m['level']),
                    'message' => $msg,
                    'context' => $context,
                    'stack' => $stack,
                    'raw' => $line,
                ];
            } else {
                // Continuation (likely stack trace)
                if ($current) {
                    $current['stack'][] = $line;
                    $current['raw'] .= "\n" . $line;
                }
            }
        }
        if ($current) {
            $entries[] = self::maskEntry($current);
        }
        return $entries;
    }

    /**
     * Filter entries by levels and keyword.
     * @param array<int, array<string, mixed>> $entries
     * @param array<int, string>|null $levels lowercase levels
     */
    public static function filter(array $entries, ?array $levels = null, ?string $keyword = null): array
    {
        $levels = $levels ? array_map('strtolower', $levels) : null;
        $kw = $keyword ? strtolower($keyword) : null;
        return array_values(array_filter($entries, function ($e) use ($levels, $kw) {
            if ($levels && !in_array(strtolower((string)($e['level'] ?? '')), $levels, true)) {
                return false;
            }
            if ($kw) {
                $hay = strtolower((string)($e['message'] ?? '')) . ' ' . strtolower((string)json_encode($e['context'] ?? '')) . ' ' . strtolower(implode(' ', $e['stack'] ?? []));
                if (strpos($hay, $kw) === false) return false;
            }
            return true;
        }));
    }

    /**
     * Mask sensitive data in a parsed entry.
     * @param array<string,mixed> $entry
     * @return array<string,mixed>
     */
    protected static function maskEntry(array $entry): array
    {
        // Mask message, context, stack, and raw
        $entry['message'] = is_string($entry['message'] ?? null)
            ? self::maskString((string)$entry['message'])
            : $entry['message'];

        if (isset($entry['context'])) {
            $entry['context'] = self::maskContext($entry['context']);
        }

        if (!empty($entry['stack']) && is_array($entry['stack'])) {
            $entry['stack'] = array_map(function ($line) {
                return is_string($line) ? self::maskString($line) : $line;
            }, $entry['stack']);
        }

        if (isset($entry['raw']) && is_string($entry['raw'])) {
            $entry['raw'] = self::maskString($entry['raw']);
        }

        return $entry;
    }

    /**
     * Recursively mask sensitive keys in context arrays.
     * @param mixed $data
     * @return mixed
     */
    protected static function maskContext($data)
    {
        if (is_array($data)) {
            $masked = [];
            foreach ($data as $k => $v) {
                if (is_string($k) && self::isSensitiveKey($k)) {
                    $masked[$k] = self::maskValue($v);
                } else {
                    $masked[$k] = self::maskContext($v);
                }
            }
            return $masked;
        }
        return $data;
    }

    protected static function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);
        $sensitive = [
            'password','pass','pwd','secret','token','api_key','apikey','api-key',
            'access_token','refresh_token','client_secret','clientsecret','authorization',
            'bearer','private_key','privatekey','app_key','appkey','app-key','app_secret','x-api-key',
        ];
        return in_array($key, $sensitive, true) || preg_match('/(password|secret|token|key)/i', $key) === 1;
    }

    /**
     * Mask sensitive substrings inside arbitrary text.
     */
    protected static function maskString(string $text): string
    {
        // Mask Authorization: Bearer <token>
        $text = preg_replace_callback('/(Authorization\s*:\s*Bearer)\s+([^\s]+)/i', function ($m) {
            return $m[1] . ' ' . self::maskToken($m[2]);
        }, $text);

        // Mask JWT-like tokens
        $text = preg_replace('/[A-Za-z0-9\-_.]{10,}\.[A-Za-z0-9\-_.]{10,}\.[A-Za-z0-9\-_.]{10,}/', '***jwt***', $text);

        // Mask common key=value or key:"value" patterns for sensitive keys
        $text = preg_replace_callback('/(?i)\b(password|pwd|pass|secret|token|api[_-]?key|app[_-]?key|client[_-]?secret|access[_-]?token|refresh[_-]?token)\b\s*[:=]\s*(["\"])?(?P<val>[^\s"\']+)(?:\2)?/', function ($m) {
            $val = $m['val'] ?? ($m[3] ?? '');
            return $m[1] . '=' . self::maskToken($val);
        }, $text);

        return $text;
    }

    protected static function maskValue($value)
    {
        if (is_string($value)) {
            return self::maskToken($value);
        }
        if (is_array($value)) {
            // Recursively mask nested arrays/objects
            return array_map(fn($v) => self::maskValue($v), $value);
        }
        return $value;
    }

    protected static function maskToken(string $value): string
    {
        $value = trim($value);
        if ($value === '') return '***';
        // Preserve small portion for debugging while hiding majority
        $len = strlen($value);
        if ($len <= 4) return str_repeat('*', $len);
        if ($len <= 8) return substr($value, 0, 1) . str_repeat('*', $len - 2) . substr($value, -1);
        return substr($value, 0, 4) . str_repeat('*', max(0, $len - 6)) . substr($value, -2);
    }
}
