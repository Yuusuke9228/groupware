<?php
use PHPUnit\Framework\TestCase;
use Services\FilePermissionService;

final class FilePermissionServiceTest extends TestCase
{
    public function testCanAccessReturnsFalseWhenPermissionRowsExistButUserDoesNotMatch(): void
    {
        $db = new class {
            public function fetchAll($sql, $params = [])
            {
                if (strpos($sql, 'SELECT organization_id FROM user_organizations') !== false) {
                    return [['organization_id' => 10]];
                }
                if (strpos($sql, 'FROM file_permissions') !== false) {
                    return [
                        ['permission_type' => 'view', 'subject_type' => 'organization', 'subject_id' => 20],
                    ];
                }
                return [];
            }

            public function fetch($sql, $params = [])
            {
                return null;
            }
        };

        $service = new FilePermissionService($db);
        $allowed = $service->canViewFolder(['id' => 3, 'created_by' => 999], ['id' => 5, 'role' => 'user']);

        $this->assertFalse($allowed);
    }

    public function testCanAccessFallsBackToOpenAccessWhenNoPermissionRowsExist(): void
    {
        $db = new class {
            public function fetchAll($sql, $params = [])
            {
                return [];
            }

            public function fetch($sql, $params = [])
            {
                return null;
            }
        };

        $service = new FilePermissionService($db);
        $allowed = $service->canViewFile(['id' => 8, 'uploaded_by' => 999, 'folder_id' => null], ['id' => 5, 'role' => 'user']);

        $this->assertTrue($allowed);
    }
}
