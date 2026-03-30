<?php

declare(strict_types=1);

namespace Core;

class I18n
{
    public const DEFAULT_LOCALE = 'ja';

    /** @var array<string, bool> */
    private const SUPPORTED_LOCALES = [
        'ja' => true,
        'en' => true,
    ];

    private const COOKIE_NAME = 'gw_locale';
    private const COOKIE_TTL = 31536000; // 365 days

    /** @var array<string, array<string, string>> */
    private static array $messages = [];

    private static string $locale = self::DEFAULT_LOCALE;
    private static bool $initialized = false;

    public static function init(?string $requestedLocale = null): void
    {
        if (self::$initialized) {
            if ($requestedLocale !== null && $requestedLocale !== '') {
                self::setLocale($requestedLocale, true);
            }
            return;
        }

        $locale = self::resolveLocale($requestedLocale);
        self::$locale = $locale;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $locale;
        }

        self::persistLocaleCookie($locale);
        self::$initialized = true;
    }

    public static function setLocale(string $locale, bool $persist = true): string
    {
        $normalized = self::normalizeLocale($locale);
        self::$locale = $normalized;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $normalized;
        }

        if ($persist) {
            self::persistLocaleCookie($normalized);
        }

        self::$initialized = true;

        return $normalized;
    }

    public static function getLocale(): string
    {
        if (!self::$initialized) {
            self::init();
        }

        return self::$locale;
    }

    /**
     * @return array<int, string>
     */
    public static function supportedLocales(): array
    {
        return array_keys(self::SUPPORTED_LOCALES);
    }

    public static function isSupported(string $locale): bool
    {
        return isset(self::SUPPORTED_LOCALES[$locale]);
    }

    /**
     * @param array<string, scalar|null> $replace
     */
    public static function t(string $key, array $replace = [], ?string $default = null): string
    {
        $locale = self::getLocale();
        $message = self::messageForLocale($locale, $key)
            ?? self::messageForLocale(self::DEFAULT_LOCALE, $key)
            ?? ($default !== null ? $default : $key);

        if ($replace !== []) {
            $pairs = [];
            foreach ($replace as $replaceKey => $value) {
                $pairs['{' . $replaceKey . '}'] = (string)$value;
            }
            $message = strtr($message, $pairs);
        }

        return $message;
    }

    /**
     * @param array<int, string> $keys
     * @return array<string, string>
     */
    public static function messagesForJs(array $keys): array
    {
        $messages = [];
        foreach ($keys as $key) {
            $messages[$key] = self::t($key);
        }

        return $messages;
    }

    public static function normalizeLocale(?string $locale): string
    {
        $value = strtolower(trim((string)$locale));
        if ($value === '') {
            return self::DEFAULT_LOCALE;
        }

        if (strpos($value, '-') !== false) {
            $value = explode('-', $value)[0];
        }

        if (strpos($value, '_') !== false) {
            $value = explode('_', $value)[0];
        }

        return self::isSupported($value) ? $value : self::DEFAULT_LOCALE;
    }

    public static function dataLocale(): string
    {
        return self::getLocale() === 'ja' ? 'ja-JP' : 'en-US';
    }

    private static function resolveLocale(?string $requestedLocale): string
    {
        if ($requestedLocale !== null && $requestedLocale !== '') {
            return self::normalizeLocale($requestedLocale);
        }

        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['locale'])) {
            return self::normalizeLocale((string)$_SESSION['locale']);
        }

        if (isset($_COOKIE[self::COOKIE_NAME])) {
            return self::normalizeLocale((string)$_COOKIE[self::COOKIE_NAME]);
        }

        return self::DEFAULT_LOCALE;
    }

    private static function persistLocaleCookie(string $locale): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        $basePath = defined('BASE_PATH') ? (string)BASE_PATH : '';
        $cookiePath = '/';
        if ($basePath !== '' && $basePath !== '/') {
            $cookiePath = rtrim($basePath, '/') . '/';
        }

        $secure = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
        setcookie(self::COOKIE_NAME, $locale, [
            'expires' => time() + self::COOKIE_TTL,
            'path' => $cookiePath,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function messageForLocale(string $locale, string $key): ?string
    {
        if (!isset(self::$messages[$locale])) {
            self::$messages[$locale] = self::loadMessages($locale);
        }

        return self::$messages[$locale][$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private static function loadMessages(string $locale): array
    {
        $path = __DIR__ . '/../config/locales/' . $locale . '.php';
        if (!is_file($path)) {
            return [];
        }

        $messages = require $path;
        if (!is_array($messages)) {
            return [];
        }

        $normalized = [];
        foreach ($messages as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }
            $normalized[$key] = (string)$value;
        }

        return $normalized;
    }
}
