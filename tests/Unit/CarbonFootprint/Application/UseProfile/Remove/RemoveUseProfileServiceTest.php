<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\UseProfile\Remove;

use App\CarbonFootprint\Application\UseProfile\Remove\RemoveUseProfileRequest;
use App\CarbonFootprint\Application\UseProfile\Remove\RemoveUseProfileService;
use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRemoved;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveUseProfileServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = UseProfileRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $useProfile = UseProfileMother::registeredUseProfile();

        $repository->add($useProfile);

        $request = new RemoveUseProfileRequest(
            useProfileId: $useProfile->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($useProfile->id()->equals($result->useProfileId));

        $useProfilePersisted = $repository->find($result->useProfileId);

        $this->assertNull($useProfilePersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof UseProfileRemoved);
    }

    public function testThrowsWhenNotFound(): void
    {
        $sut = $this->createSUT(UseProfileRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);

        $sut(new RemoveUseProfileRequest(useProfileId: UseProfileId::generate()), UserMother::adminUser());
    }

    public function testOwnerCannotRemove(): void
    {
        $repository = UseProfileRepositoryMother::inMemory();
        $useProfile = UseProfileMother::random();
        $repository->add($useProfile);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RemoveUseProfileRequest(useProfileId: $useProfile->id()), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testIsHandle(): void
    {
        $repository = UseProfileRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $useProfile = UseProfileMother::random();

        $result = $sut->isHandled(new RemoveUseProfileRequest(
            useProfileId: $useProfile->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(UseProfileRepository $repository): RemoveUseProfileService
    {
        return new RemoveUseProfileService($repository, new UseProfileAuthorizer());
    }
}
