<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\UseProfile\Register;

use App\CarbonFootprint\Application\UseProfile\Register\RegisterUseProfileRequest;
use App\CarbonFootprint\Application\UseProfile\Register\RegisterUseProfileService;
use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRegistered;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileTranslated;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterUseProfileServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = UseProfileRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterUseProfileRequest(
            name: 'Lavadora clase A',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->useProfileId);
        $this->assertEquals(36, strlen((string) $result->useProfileId));
        $this->assertEquals('Lavadora clase A', $result->name);

        $useProfilePersisted = $repository->find($result->useProfileId);

        $this->assertNotNull($useProfilePersisted);
        $this->assertSame(Unit::Piece, $useProfilePersisted->unit());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof UseProfileRegistered);
        $this->assertEquals('Lavadora clase A', $lastEvent->name());
        $this->assertSame(Unit::Piece, $lastEvent->unit());
        $this->assertTrue($useProfilePersisted->id()->equals($lastEvent->useProfileId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = UseProfileRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterUseProfileRequest(
            name: 'changedName',
            unit: Unit::Piece,
        );

        $result = $sut($request, UserMother::adminUser());

        $useProfilePersisted = $repository->find($result->useProfileId);

        $this->assertNotNull($useProfilePersisted);
        $this->assertEquals('changedName', $useProfilePersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof UseProfileTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($useProfilePersisted->id()->equals($lastEvent->useProfileId()));
    }

    public function testOwnerCannotRegister(): void
    {
        $repository = UseProfileRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RegisterUseProfileRequest(name: 'test'), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testIsHandle(): void
    {
        $repository = UseProfileRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterUseProfileRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(UseProfileRepository $repository, Locale $locale): RegisterUseProfileService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterUseProfileService(
            useProfileRepository: $repository,
            authorizer: new UseProfileAuthorizer(),
        );
    }
}
