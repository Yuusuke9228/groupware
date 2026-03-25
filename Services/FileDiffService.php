<?php
namespace Services;

class FileDiffService
{
    public static function compareVersions($leftPath, $rightPath, $leftName, $rightName, $leftMime = '', $rightMime = '')
    {
        if (!is_file($leftPath) || !is_file($rightPath)) {
            return [
                'mode' => 'missing',
                'summary' => '比較対象ファイルが見つかりません。',
                'diff' => ''
            ];
        }

        $leftSize = filesize($leftPath);
        $rightSize = filesize($rightPath);
        $leftText = self::isTextFile($leftName, $leftMime, $leftSize);
        $rightText = self::isTextFile($rightName, $rightMime, $rightSize);

        if (!$leftText || !$rightText) {
            return [
                'mode' => 'binary',
                'summary' => sprintf('バイナリ比較です。サイズ: %s -> %s', self::formatBytes($leftSize), self::formatBytes($rightSize)),
                'diff' => ''
            ];
        }

        if ($leftSize > 262144 || $rightSize > 262144) {
            return [
                'mode' => 'summary',
                'summary' => 'テキストファイルですがサイズが大きいため差分全文表示は省略しました。',
                'diff' => ''
            ];
        }

        $leftContent = file_get_contents($leftPath);
        $rightContent = file_get_contents($rightPath);
        $diff = self::buildUnifiedDiff($leftContent, $rightContent, $leftName, $rightName);

        return [
            'mode' => 'text',
            'summary' => $diff === '' ? '差分はありません。' : '行単位の差分を表示しています。',
            'diff' => $diff,
        ];
    }

    public static function isTextFile($filename, $mimeType = '', $size = 0)
    {
        $extension = strtolower(pathinfo((string)$filename, PATHINFO_EXTENSION));
        $textExtensions = ['txt', 'md', 'csv', 'tsv', 'json', 'xml', 'html', 'htm', 'css', 'js', 'ts', 'tsx', 'jsx', 'php', 'sql', 'yml', 'yaml', 'ini', 'log'];
        if (in_array($extension, $textExtensions, true)) {
            return true;
        }

        if ($mimeType !== '' && (
            strpos($mimeType, 'text/') === 0 ||
            in_array($mimeType, ['application/json', 'application/xml', 'application/javascript'], true)
        )) {
            return true;
        }

        return $size > 0 && $size <= 1024 * 1024 && $mimeType === '';
    }

    public static function buildUnifiedDiff($oldContent, $newContent, $oldName = 'old', $newName = 'new')
    {
        $oldLines = preg_split('/\R/', (string)$oldContent);
        $newLines = preg_split('/\R/', (string)$newContent);

        if ($oldLines === $newLines) {
            return '';
        }

        $matrix = [];
        $oldCount = count($oldLines);
        $newCount = count($newLines);

        for ($i = 0; $i <= $oldCount; $i++) {
            $matrix[$i] = array_fill(0, $newCount + 1, 0);
        }

        for ($i = $oldCount - 1; $i >= 0; $i--) {
            for ($j = $newCount - 1; $j >= 0; $j--) {
                if ($oldLines[$i] === $newLines[$j]) {
                    $matrix[$i][$j] = $matrix[$i + 1][$j + 1] + 1;
                } else {
                    $matrix[$i][$j] = max($matrix[$i + 1][$j], $matrix[$i][$j + 1]);
                }
            }
        }

        $diff = [
            '--- ' . $oldName,
            '+++ ' . $newName,
            '@@ -1,' . $oldCount . ' +1,' . $newCount . ' @@'
        ];

        $i = 0;
        $j = 0;
        while ($i < $oldCount && $j < $newCount) {
            if ($oldLines[$i] === $newLines[$j]) {
                $diff[] = ' ' . $oldLines[$i];
                $i++;
                $j++;
                continue;
            }

            if ($matrix[$i + 1][$j] >= $matrix[$i][$j + 1]) {
                $diff[] = '-' . $oldLines[$i];
                $i++;
            } else {
                $diff[] = '+' . $newLines[$j];
                $j++;
            }
        }

        while ($i < $oldCount) {
            $diff[] = '-' . $oldLines[$i++];
        }
        while ($j < $newCount) {
            $diff[] = '+' . $newLines[$j++];
        }

        return implode("\n", $diff);
    }

    private static function formatBytes($bytes)
    {
        $bytes = (int)$bytes;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
