<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Update;

use App\Library\Application\Media\Update\UpdateMediaRequest;
use App\Library\Application\Media\Update\UpdateMediaService;
use App\Library\Domain\Model\Media\MediaDetailsChanged;
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

class UpdateMediaServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = new InMemoryMediaRepository();

        $media = MediaMother::uploadedMedia();

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new UpdateMediaRequest(
                mediaId: $media->id(),
                name: 'Logotipo corporativo',
                description: 'El logotipo en color',
                alternativeText: 'Logotipo',
            ),
            UserMother::adminUser(),
        );

        $this->assertEquals('Logotipo corporativo', $result->name);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertTrue($lastEvent instanceof MediaDetailsChanged);
        $this->assertEquals('Logotipo', $lastEvent->alternativeText());
    }

    public function testNothingIsRecordedWhenNothingChanges(): void
    {
        $repository = new InMemoryMediaRepository();

        $media = MediaMother::uploadedMedia();

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $sut(
            new UpdateMediaRequest(
                mediaId: $media->id(),
                name: $media->name(),
                description: $media->description(),
                alternativeText: $media->alternativeText(),
            ),
            UserMother::adminUser(),
        );

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertFalse($lastEvent instanceof MediaDetailsChanged);
    }

    public function testTheVisibilityIsChanged(): void
    {
        $repository = new InMemoryMediaRepository();

        $media = MediaMother::uploadedMedia();

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new UpdateMediaRequest(
                mediaId: $media->id(),
                name: $media->name(),
                visibility: MediaVisibility::Public,
            ),
            UserMother::adminUser(),
        );

        $this->assertEquals(MediaVisibility::Public, $result->visibility);
        $this->assertTrue($this->spySubscriber->lastEvent() instanceof MediaVisibilityChanged);
    }

    /**
     * The visibility belongs to whoever may edit the file, not only to the
     * administrators of the account.
     */
    public function testAnyUserEditingTheMediaChangesTheVisibility(): void
    {
        $repository = new InMemoryMediaRepository();

        $accountId = AccountId::generate();

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $accountId);

        $result = $sut(
            new UpdateMediaRequest(
                mediaId: $media->id(),
                name: $media->name(),
                visibility: MediaVisibility::System,
            ),
            $owner,
        );

        $this->assertEquals(MediaVisibility::System, $result->visibility);
        $this->assertTrue($this->spySubscriber->lastEvent() instanceof MediaVisibilityChanged);
    }

    public function testUnknownMedia(): void
    {
        $this->expectException(MediaNotFoundException::class);

        $sut = $this->createSUT(new InMemoryMediaRepository());

        $sut(new UpdateMediaRequest(mediaId: MediaId::generate(), name: 'Whatever'), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryMediaRepository();

        $accountId = AccountId::generate();

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $foreignUser = UserMother::fromUsernameAndRoleAndAccount('foreign@dediez.software', Role::Owner, AccountId::generate());

        $sut(new UpdateMediaRequest(mediaId: $media->id(), name: 'Whatever'), $foreignUser);
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT(new InMemoryMediaRepository());

        $this->assertTrue($sut->isHandled(new UpdateMediaRequest(mediaId: MediaId::generate(), name: 'Whatever')));
    }

    private function createSUT(InMemoryMediaRepository $repository): UpdateMediaService
    {
        return new UpdateMediaService($repository, MediaAuthorizerMother::create());
    }
}
