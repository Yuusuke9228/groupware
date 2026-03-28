<?php

namespace Services;

class WebDatabaseValidationService
{
    public static function validateRecordFields(
        array $fields,
        array $fieldValues,
        array $fileValues = [],
        array $existingValues = [],
        ?callable $uniqueChecker = null
    ): array {
        $errors = [];

        foreach ($fields as $field) {
            $fieldId = (int)($field['id'] ?? 0);
            if ($fieldId <= 0) {
                continue;
            }

            $type = (string)($field['type'] ?? 'text');
            $required = !empty($field['required']);
            $value = $fieldValues[$fieldId] ?? null;
            $existing = $existingValues[$fieldId] ?? null;
            $errorKey = 'fields.' . $fieldId;

            if ($required && self::isEmptyValue($type, $value, $fileValues[$fieldId] ?? null, $existing)) {
                $errors[$errorKey] = '必須項目です';
                continue;
            }

            if (self::isSkipValidationValue($type, $value)) {
                continue;
            }

            switch ($type) {
                case 'number':
                case 'currency':
                    if (!is_numeric($value)) {
                        $errors[$errorKey] = '数値で入力してください';
                    }
                    break;

                case 'percent':
                    if (!is_numeric($value)) {
                        $errors[$errorKey] = '数値で入力してください';
                    } elseif ((float)$value < 0 || (float)$value > 100) {
                        $errors[$errorKey] = '0〜100の範囲で入力してください';
                    }
                    break;

                case 'email':
                    if (!filter_var((string)$value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$errorKey] = 'メールアドレス形式が正しくありません';
                    }
                    break;

                case 'url':
                    if (!filter_var((string)$value, FILTER_VALIDATE_URL)) {
                        $errors[$errorKey] = 'URL形式が正しくありません';
                    }
                    break;

                case 'date':
                    if (!self::isValidDate((string)$value)) {
                        $errors[$errorKey] = '日付形式が正しくありません';
                    }
                    break;

                case 'datetime':
                    if (!self::isValidDateTime((string)$value)) {
                        $errors[$errorKey] = '日時形式が正しくありません';
                    }
                    break;

                case 'select':
                case 'radio':
                    if (!self::isAllowedOptionValue($field, (string)$value)) {
                        $errors[$errorKey] = '選択肢にない値です';
                    }
                    break;

                case 'checkbox':
                    $values = is_array($value) ? $value : explode(',', (string)$value);
                    foreach ($values as $item) {
                        if ((string)$item === '') {
                            continue;
                        }
                        if (!self::isAllowedOptionValue($field, (string)$item)) {
                            $errors[$errorKey] = '選択肢にない値が含まれています';
                            break;
                        }
                    }
                    break;

                case 'relation':
                    if (is_array($value)) {
                        foreach ($value as $targetId) {
                            if ((string)$targetId === '') {
                                continue;
                            }
                            if (!ctype_digit((string)$targetId) || (int)$targetId <= 0) {
                                $errors[$errorKey] = '参照レコードが不正です';
                                break;
                            }
                        }
                    } elseif ($value !== null && $value !== '' && (!ctype_digit((string)$value) || (int)$value <= 0)) {
                        $errors[$errorKey] = '参照レコードが不正です';
                    }
                    break;
            }

            if (!empty($errors[$errorKey])) {
                continue;
            }

            if (!empty($field['unique_value']) && $uniqueChecker !== null) {
                $normalized = self::normalizeForUniqueCheck($type, $value);
                if ($normalized !== '' && $uniqueChecker($fieldId, $normalized) === true) {
                    $errors[$errorKey] = '同じ値が既に登録されています';
                }
            }
        }

        return $errors;
    }

    private static function isSkipValidationValue(string $type, $value): bool
    {
        if ($type === 'file') {
            return true;
        }
        if ($value === null) {
            return true;
        }
        if (is_string($value) && trim($value) === '') {
            return true;
        }
        if (is_array($value) && empty($value)) {
            return true;
        }
        return false;
    }

    private static function isEmptyValue(string $type, $value, $fileValue, $existingValue): bool
    {
        if ($type === 'file') {
            if (!empty($fileValue)) {
                return false;
            }
            if (!empty($existingValue)) {
                return false;
            }
            return true;
        }

        if ($type === 'checkbox' || $type === 'relation') {
            if (is_array($value)) {
                return count(array_filter($value, static function ($v) {
                    return $v !== null && (string)$v !== '';
                })) === 0;
            }
            return $value === null || trim((string)$value) === '';
        }

        return $value === null || trim((string)$value) === '';
    }

    private static function decodeOptions(array $field): array
    {
        if (empty($field['options'])) {
            return [];
        }
        $decoded = json_decode((string)$field['options'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }
        return $decoded;
    }

    private static function isAllowedOptionValue(array $field, string $value): bool
    {
        $options = self::decodeOptions($field);
        if (empty($options)) {
            return true;
        }
        foreach ($options as $option) {
            if ((string)($option['value'] ?? '') === $value) {
                return true;
            }
        }
        return false;
    }

    private static function isValidDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        return checkdate($m, $d, $y);
    }

    private static function isValidDateTime(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/', $value)) {
            return false;
        }
        return strtotime($value) !== false;
    }

    private static function normalizeForUniqueCheck(string $type, $value): string
    {
        if ($type === 'checkbox' && is_array($value)) {
            $items = array_values(array_filter(array_map('strval', $value), static function ($v) {
                return $v !== '';
            }));
            sort($items);
            return implode(',', $items);
        }
        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $encoded === false ? '' : $encoded;
        }
        return trim((string)$value);
    }
}
