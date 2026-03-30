<?php

declare(strict_types=1);

use Core\I18n;

if (!function_exists('t')) {
    /**
     * @param array<string, scalar|null> $replace
     */
    function t(string $key, array $replace = [], ?string $default = null): string
    {
        return I18n::t($key, $replace, $default);
    }
}

if (!function_exists('get_locale')) {
    function get_locale(): string
    {
        return I18n::getLocale();
    }
}

if (!function_exists('set_locale')) {
    function set_locale(string $locale, bool $persist = true): string
    {
        return I18n::setLocale($locale, $persist);
    }
}

if (!function_exists('locale_supported')) {
    function locale_supported(string $locale): bool
    {
        return I18n::isSupported(I18n::normalizeLocale($locale));
    }
}

if (!function_exists('current_data_locale')) {
    function current_data_locale(): string
    {
        return I18n::dataLocale();
    }
}

if (!function_exists('tr_text')) {
    function tr_text(string $ja, string $en): string
    {
        return I18n::getLocale() === 'ja' ? $ja : $en;
    }
}
