<?php
use PHPUnit\Framework\TestCase;
use Services\FileDiffService;

final class FileDiffServiceTest extends TestCase
{
    public function testBuildUnifiedDiffShowsAddedAndRemovedLines(): void
    {
        $diff = FileDiffService::buildUnifiedDiff("A\nB\nC\n", "A\nX\nC\n", 'old.txt', 'new.txt');

        $this->assertStringContainsString('--- old.txt', $diff);
        $this->assertStringContainsString('+++ new.txt', $diff);
        $this->assertStringContainsString('-B', $diff);
        $this->assertStringContainsString('+X', $diff);
    }

    public function testIsTextFileRecognizesCommonExtensions(): void
    {
        $this->assertTrue(FileDiffService::isTextFile('sample.sql', '', 100));
        $this->assertTrue(FileDiffService::isTextFile('sample.bin', 'text/plain', 100));
        $this->assertFalse(FileDiffService::isTextFile('image.png', 'image/png', 2048));
    }
}
