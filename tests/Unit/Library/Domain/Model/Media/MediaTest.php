<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Media;

use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Media\MediaMoved;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

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

    public function testMovingToAFolderPublishesTheEventAndKeepsTheFolder(): void
    {
        DomainEventPublisher::instance()->reset();

        $spySubscriber = new SpyDomainEventSubscriber();

        $media = MediaMother::uploadedMedia();
        $folderId = FolderId::generate();

        $media->moveToFolder($folderId);

        $this->assertNotNull($media->folderId());
        $this->assertTrue($media->folderId()->equals($folderId));

        $event = $spySubscriber->lastEvent();

        $this->assertInstanceOf(MediaMoved::class, $event);

        $rebuilt = MediaMoved::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertTrue($rebuilt->mediaId()->equals($media->id()));
        $this->assertNotNull($rebuilt->folderId());
        $this->assertTrue($rebuilt->folderId()->equals($folderId));
    }

    public function testMovingOutOfTheFolderClearsIt(): void
    {
        DomainEventPublisher::instance()->reset();

        $spySubscriber = new SpyDomainEventSubscriber();

        $media = MediaMother::inFolder('logo.png', FolderId::generate());

        $media->moveToFolder(null);

        $this->assertNull($media->folderId());

        $event = $spySubscriber->lastEvent();

        $this->assertInstanceOf(MediaMoved::class, $event);

        $rebuilt = MediaMoved::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertNull($rebuilt->folderId());
    }
}
