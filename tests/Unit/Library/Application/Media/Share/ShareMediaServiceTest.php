<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Share;

use App\Library\Application\Media\Share\ShareMediaRequest;
use App\Library\Application\Media\Share\ShareMediaService;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Domain\Model\Media\MediaVisibilityChanged;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Application\Media\MediaAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ShareMediaServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    /**
     * Any user widens the audience of the files it uses: it is the field requiring it,
     * not a decision of the user, so it does not need the admin rights the library asks for.
     */
    public function testUseCase(): void
    {
        $repository = new InMemoryMediaRepository();

        $media = MediaMother::uploadedMedia();

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new ShareMediaRequest($media->id(), MediaVisibility::System),
            UserMother::fromUsernameAndRole('user@dediez.software', Role::User),
        );

        $this->assertEquals(MediaVisibility::System, $result->visibility);

        $mediaPersisted = $repository->find($media->id());

        $this->assertNotNull($mediaPersisted);
        $this->assertTrue($mediaPersisted->isShared());

        $this->assertTrue($this->spySubscriber->lastEvent() instanceof MediaVisibilityChanged);
    }

    public function testAFileAlreadyReachingAWiderAudienceIsLeftUntouched(): void
    {
        $repository = new InMemoryMediaRepository();

        $media = MediaMother::fromNameAndVisibility('terms.pdf', MediaVisibility::Public);

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new ShareMediaRequest($media->id(), MediaVisibility::System),
            UserMother::adminUser(),
        );

        $this->assertEquals(MediaVisibility::Public, $result->visibility);
        $this->assertFalse($this->spySubscriber->lastEvent() instanceof MediaVisibilityChanged);
    }

    public function testTheFilesOfAnotherAccountAreNotShared(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryMediaRepository();

        $media = MediaMother::fromNameAndAccount('foreign.png', AccountId::generate());

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $sut(
            new ShareMediaRequest($media->id(), MediaVisibility::System),
            UserMother::fromUsernameAndRole('user@dediez.software', Role::User),
        );
    }

    public function testAnUnknownFileIsRejected(): void
    {
        $this->expectException(MediaNotFoundException::class);

        $sut = $this->createSUT(new InMemoryMediaRepository());

        $sut(new ShareMediaRequest(MediaId::generate(), MediaVisibility::System), UserMother::adminUser());
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT(new InMemoryMediaRepository());

        $this->assertTrue($sut->isHandled(new ShareMediaRequest(MediaId::generate())));
    }

    private function createSUT(InMemoryMediaRepository $repository): ShareMediaService
    {
        return new ShareMediaService($repository, MediaAuthorizerMother::create());
    }
}
