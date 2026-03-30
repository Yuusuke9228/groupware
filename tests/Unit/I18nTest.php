<?php

use Core\I18n;
use PHPUnit\Framework\TestCase;

final class I18nTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
        }
    }

    public function testNormalizeLocale(): void
    {
        $this->assertSame('ja', I18n::normalizeLocale('ja'));
        $this->assertSame('en', I18n::normalizeLocale('en_US'));
        $this->assertSame('en', I18n::normalizeLocale('en-US'));
        $this->assertSame('ja', I18n::normalizeLocale('fr'));
    }

    public function testMessageLookupWithFallback(): void
    {
        I18n::setLocale('en', false);
        $this->assertSame('Language', I18n::t('lang.switch'));

        I18n::setLocale('ja', false);
        $this->assertSame('言語', I18n::t('lang.switch'));

        I18n::setLocale('en', false);
        $this->assertSame('non.existing.key', I18n::t('non.existing.key'));
        $this->assertSame('fallback', I18n::t('non.existing.key', [], 'fallback'));
    }
}
