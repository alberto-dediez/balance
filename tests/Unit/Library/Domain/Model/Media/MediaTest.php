<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Media;

use App\Library\Domain\Model\Media\MediaVisibility;
use PHPUnit\Framework\TestCase;

/**
 * Two independent decisions live in a file: the audience able to open it, which the
 * places using the file impose, and its presence in the library of the other accounts,
 * which only its owner decides.
 */
class MediaTest extends TestCase
{
    public function testWideningTheAudienceDoesNotShareTheFile(): void
    {
        $media = MediaMother::uploadedMedia();

        $media->changeVisibility(MediaVisibility::System);

        $this->assertTrue($media->isAccessibleOutsideAccount());
        $this->assertFalse($media->isShared());
    }

    public function testSharingAFileMakesItReachableByEveryAccount(): void
    {
        $media = MediaMother::uploadedMedia();

        $this->assertFalse($media->isAccessibleOutsideAccount());

        $media->changeSharing(true);

        $this->assertTrue($media->isShared());
        $this->assertEquals(MediaVisibility::System, $media->visibility());
    }

    public function testSharingAPublicFileKeepsItsWiderAudience(): void
    {
        $media = MediaMother::fromNameAndVisibility('terms.pdf', MediaVisibility::Public);

        $media->changeSharing(true);

        $this->assertTrue($media->isShared());
        $this->assertEquals(MediaVisibility::Public, $media->visibility());
    }

    public function testNarrowingTheAudienceToTheAccountWithdrawsTheFileFromTheLibrary(): void
    {
        $media = MediaMother::sharedMedia();

        $media->changeVisibility(MediaVisibility::Account);

        $this->assertFalse($media->isShared());
        $this->assertEquals(MediaVisibility::Account, $media->visibility());
    }

    public function testAFileUploadedAsSharedReachesEveryAccount(): void
    {
        $media = MediaMother::sharedMedia();

        $this->assertTrue($media->isShared());
        $this->assertTrue($media->isAccessibleOutsideAccount());
    }
}
