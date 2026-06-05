<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\UpdateProfile;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\UpdateProfile\UpdateProfileRequest;
use App\Security\Application\User\UpdateProfile\UpdateProfileService;
use App\Security\Domain\Model\User\PreferredLocaleChanged;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateProfileServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = new InMemoryUserRepository();

        $user = UserMother::random();

        $repository->add($user);

        $sut = $this->createSUT($repository);

        $request = new UpdateProfileRequest(
            userId: $user->id(),
            preferredLocale: Locale::English,
        );

        $result = $sut($request, $user);

        $this->assertTrue($user->id()->equals($result->userId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(PreferredLocaleChanged::class, $lastEvent);
        $this->assertTrue($user->id()->equals($lastEvent->userId()));
        $this->assertEquals(Locale::English, $lastEvent->locale());

        $persisted = $repository->find($result->userId);

        $this->assertNotNull($persisted);
        $this->assertEquals(Locale::English, $persisted->preferredLocale());
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();

        $user = UserMother::random();
        $otherUser = UserMother::ownerUser();

        $repository->add($user);

        $sut = $this->createSUT($repository);

        $request = new UpdateProfileRequest(
            userId: $user->id(),
            preferredLocale: Locale::Spanish,
        );

        $sut($request, $otherUser);
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT(new InMemoryUserRepository());

        $user = UserMother::random();

        $result = $sut->isHandled(new UpdateProfileRequest(
            userId: $user->id(),
            preferredLocale: Locale::Spanish,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryUserRepository $repository): UpdateProfileService
    {
        return new UpdateProfileService($repository, UserAuthorizerMother::create());
    }
}
