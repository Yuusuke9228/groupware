<?php

declare(strict_types=1);

namespace Core;

class RuntimeI18n
{
    private const MAP_PATH = __DIR__ . '/../config/runtime_i18n_en.php';
    private const CACHE_PATH = __DIR__ . '/../storage/i18n/runtime_i18n_en_cache.json';
    private const MAX_API_CALLS_PER_REQUEST = 24;

    /** @var array<string, string>|null */
    private static ?array $map = null;
    /** @var array<string, string> */
    private static array $requestCache = [];
    /** @var array<string, string> */
    private static array $dirtyCache = [];
    private static int $apiCalls = 0;
    private static bool $shutdownRegistered = false;

    public static function enabled(): bool
    {
        if (!function_exists('get_locale')) {
            return false;
        }

        return get_locale() === 'en';
    }

    public static function renderPhp(string $path, array $variables = []): void
    {
        if ($variables !== []) {
            extract($variables);
        }

        ob_start();
        require $path;
        $html = (string)ob_get_clean();

        echo self::translateHtml($html);
    }

    public static function translateHtml(string $html): string
    {
        if (!self::enabled() || $html === '' || !self::containsJapanese($html)) {
            return $html;
        }

        $protectedBlocks = [];
        $placeholderPrefix = '__GW_I18N_BLOCK_';
        $index = 0;

        // script/style 内は変換しない（挙動破壊防止）
        $html = (string)preg_replace_callback(
            '#<(script|style)\b[^>]*>.*?</\1>#isu',
            static function (array $matches) use (&$protectedBlocks, &$index, $placeholderPrefix): string {
                $token = $placeholderPrefix . $index . '__';
                $protectedBlocks[$token] = $matches[0];
                $index++;
                return $token;
            },
            $html
        );

        $html = (string)preg_replace_callback(
            '/>([^<]+)</u',
            static function (array $matches): string {
                $text = $matches[1];
                if (!self::containsJapanese($text)) {
                    return $matches[0];
                }
                return '>' . self::translateText($text) . '<';
            },
            $html
        );

        $html = (string)preg_replace_callback(
            '/(\s(?:placeholder|title|aria-label|alt|data-original-title|data-bs-original-title)\s*=\s*)(["\'])(.*?)(\2)/u',
            static function (array $matches): string {
                $text = $matches[3];
                if (!self::containsJapanese($text)) {
                    return $matches[0];
                }
                return $matches[1] . $matches[2] . self::translateText($text) . $matches[4];
            },
            $html
        );

        if ($protectedBlocks !== []) {
            $html = strtr($html, $protectedBlocks);
        }

        return $html;
    }

    /**
     * @param mixed $payload
     * @return mixed
     */
    public static function translateApiPayload($payload)
    {
        if (!self::enabled()) {
            return $payload;
        }

        return self::translateApiNode($payload, null);
    }

    public static function translatePlainText(string $text): string
    {
        return self::translateText($text);
    }

    private static function translateText(string $text): string
    {
        if ($text === '' || !self::containsJapanese($text)) {
            return $text;
        }

        if (isset(self::$requestCache[$text])) {
            return self::$requestCache[$text];
        }

        $leading = '';
        $trailing = '';
        if (preg_match('/^\s+/u', $text, $m)) {
            $leading = $m[0];
        }
        if (preg_match('/\s+$/u', $text, $m)) {
            $trailing = $m[0];
        }
        $core = trim($text);

        if ($core === '') {
            self::$requestCache[$text] = $text;
            return $text;
        }

        $map = self::map();
        $translated = $map[$core] ?? strtr($core, $map);

        if ($translated === '' || $translated === $core) {
            $translated = self::translateViaApi($core);
        }

        $result = $leading . $translated . $trailing;
        self::$requestCache[$text] = $result;

        return $result;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function translateApiNode($value, ?string $key)
    {
        if (is_string($value)) {
            if (in_array((string)$key, ['message', 'error', 'status_message'], true)) {
                return self::translateText($value);
            }
            return $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        $translated = [];
        foreach ($value as $k => $v) {
            $translated[$k] = self::translateApiNode($v, is_string($k) ? $k : null);
        }
        return $translated;
    }

    private static function containsJapanese(string $text): bool
    {
        return preg_match('/[\x{3040}-\x{30ff}\x{3400}-\x{4dbf}\x{4e00}-\x{9fff}]/u', $text) === 1;
    }

    /**
     * @return array<string, string>
     */
    private static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $map = [];
        if (is_file(self::MAP_PATH)) {
            $loaded = require self::MAP_PATH;
            if (is_array($loaded)) {
                foreach ($loaded as $k => $v) {
                    if (is_string($k) && is_string($v) && $k !== '') {
                        $map[$k] = $v;
                    }
                }
            }
        }

        $cache = self::readCacheFile();
        foreach ($cache as $k => $v) {
            $map[$k] = $v;
        }

        self::$map = $map;
        return self::$map;
    }

    /**
     * @return array<string, string>
     */
    private static function readCacheFile(): array
    {
        if (!is_file(self::CACHE_PATH)) {
            return [];
        }

        $raw = @file_get_contents(self::CACHE_PATH);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $k => $v) {
            if (is_string($k) && is_string($v)) {
                $normalized[$k] = $v;
            }
        }
        return $normalized;
    }

    private static function translateViaApi(string $text): string
    {
        if (!self::enabled()) {
            return $text;
        }

        if (isset(self::$requestCache[$text])) {
            return self::$requestCache[$text];
        }

        if (mb_strlen($text) > 220) {
            return $text;
        }

        if (self::$apiCalls >= self::MAX_API_CALLS_PER_REQUEST) {
            return $text;
        }

        self::$apiCalls++;
        $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=ja&tl=en&dt=t&q=' . rawurlencode($text);

        $context = stream_context_create([
            'http' => [
                'timeout' => 2.8,
                'ignore_errors' => true,
                'header' => "User-Agent: GroupwareRuntimeI18n/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if (!is_string($raw) || $raw === '') {
            return $text;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded[0]) || !is_array($decoded[0])) {
            return $text;
        }

        $translated = '';
        foreach ($decoded[0] as $segment) {
            if (is_array($segment) && isset($segment[0]) && is_string($segment[0])) {
                $translated .= $segment[0];
            }
        }

        $translated = trim(preg_replace('/\s+/u', ' ', $translated) ?? '');
        if ($translated === '') {
            return $text;
        }

        self::rememberRuntimeTranslation($text, $translated);
        return $translated;
    }

    private static function rememberRuntimeTranslation(string $src, string $dst): void
    {
        if ($src === '' || $dst === '') {
            return;
        }

        $map = self::map();
        $map[$src] = $dst;
        self::$map = $map;
        self::$dirtyCache[$src] = $dst;
        self::$requestCache[$src] = $dst;

        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function([self::class, 'flushDirtyCache']);
        }
    }

    public static function flushDirtyCache(): void
    {
        if (self::$dirtyCache === []) {
            return;
        }

        $dir = dirname(self::CACHE_PATH);
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $existing = self::readCacheFile();
        foreach (self::$dirtyCache as $k => $v) {
            $existing[$k] = $v;
        }
        self::$dirtyCache = [];

        $json = json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (!is_string($json)) {
            return;
        }

        @file_put_contents(self::CACHE_PATH, $json, LOCK_EX);
        @chmod(self::CACHE_PATH, 0600);
    }
}

