<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Media;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaPath;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;

class MediaPathTest extends TestCase
{
    public function testWithAccount(): void
    {
        $mediaId = MediaId::generate();
        $accountId = AccountId::generate();

        $this->assertSame(
            sprintf('%s/%s.png', (string) $accountId, (string) $mediaId),
            MediaPath::from($mediaId, $accountId, 'png'),
        );
    }

    public function testWithoutAccount(): void
    {
        $mediaId = MediaId::generate();

        $this->assertSame(sprintf('shared/%s.pdf', (string) $mediaId), MediaPath::from($mediaId, null, 'pdf'));
    }

    public function testWithoutExtension(): void
    {
        $mediaId = MediaId::generate();

        $this->assertSame(sprintf('shared/%s', (string) $mediaId), MediaPath::from($mediaId, null, ''));
    }
}
