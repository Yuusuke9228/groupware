<?php
use PHPUnit\Framework\TestCase;
use Services\NotificationRecipientHelper;

final class NotificationRecipientHelperTest extends TestCase
{
    public function testUniqueRecipientsRemovesDuplicatesAndExcludedUsers(): void
    {
        $recipients = NotificationRecipientHelper::uniqueRecipients([1, 2, 2, 3, 0, -1, 4], [2, 4]);

        $this->assertSame([1, 3], $recipients);
    }
}
