<?php

use PHPUnit\Framework\TestCase;
use Services\WebDatabaseValidationService;

final class WebDatabaseValidationServiceTest extends TestCase
{
    public function testRequiredAndTypeValidation(): void
    {
        $fields = [
            ['id' => 1, 'type' => 'text', 'required' => 1],
            ['id' => 2, 'type' => 'number', 'required' => 0],
            ['id' => 3, 'type' => 'percent', 'required' => 0],
            ['id' => 4, 'type' => 'email', 'required' => 0],
            ['id' => 5, 'type' => 'url', 'required' => 0],
            ['id' => 6, 'type' => 'date', 'required' => 0],
        ];

        $errors = WebDatabaseValidationService::validateRecordFields($fields, [
            1 => '',
            2 => 'abc',
            3 => '120',
            4 => 'bad-email',
            5 => 'not-url',
            6 => '2026-13-40',
        ]);

        $this->assertArrayHasKey('fields.1', $errors);
        $this->assertArrayHasKey('fields.2', $errors);
        $this->assertArrayHasKey('fields.3', $errors);
        $this->assertArrayHasKey('fields.4', $errors);
        $this->assertArrayHasKey('fields.5', $errors);
        $this->assertArrayHasKey('fields.6', $errors);
    }

    public function testSelectAndCheckboxOptionsValidation(): void
    {
        $options = json_encode([
            ['value' => 'a', 'label' => 'A'],
            ['value' => 'b', 'label' => 'B'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $fields = [
            ['id' => 10, 'type' => 'select', 'required' => 0, 'options' => $options],
            ['id' => 11, 'type' => 'checkbox', 'required' => 0, 'options' => $options],
        ];

        $errors = WebDatabaseValidationService::validateRecordFields($fields, [
            10 => 'x',
            11 => ['a', 'z'],
        ]);

        $this->assertArrayHasKey('fields.10', $errors);
        $this->assertArrayHasKey('fields.11', $errors);
    }

    public function testUniqueCheckHookIsApplied(): void
    {
        $fields = [
            ['id' => 20, 'type' => 'text', 'required' => 0, 'unique_value' => 1],
        ];

        $errors = WebDatabaseValidationService::validateRecordFields(
            $fields,
            [20 => 'DUPLICATED'],
            [],
            [],
            static function (int $fieldId, string $value): bool {
                return $fieldId === 20 && $value === 'DUPLICATED';
            }
        );

        $this->assertArrayHasKey('fields.20', $errors);
    }

    public function testRequiredFileAcceptsExistingFileWithoutNewUpload(): void
    {
        $fields = [
            ['id' => 30, 'type' => 'file', 'required' => 1],
        ];
        $existing = [
            30 => ['name' => 'sample.pdf', 'path' => 'uploads/webdatabase/sample.pdf'],
        ];

        $errors = WebDatabaseValidationService::validateRecordFields(
            $fields,
            [],
            [],
            $existing
        );

        $this->assertSame([], $errors);
    }
}
