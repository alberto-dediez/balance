<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Process\Register;

use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Application\Process\Register\RegisterProcessRequest;
use App\CarbonFootprint\Application\Process\Register\RegisterProcessService;
use App\CarbonFootprint\Domain\Model\Process\ProcessRegistered;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\CarbonFootprint\Domain\Model\Process\ProcessTranslated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterProcessServiceTest extends TestCase
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
        $repository = ProcessRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterProcessRequest(
            name: 'Mecanizado de pieza',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->processId);
        $this->assertEquals(36, strlen((string) $result->processId));
        $this->assertEquals('Mecanizado de pieza', $result->name);

        $processPersisted = $repository->find($result->processId);

        $this->assertNotNull($processPersisted);
        $this->assertSame(Unit::Piece, $processPersisted->unit());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProcessRegistered);
        $this->assertEquals('Mecanizado de pieza', $lastEvent->name());
        $this->assertSame(Unit::Piece, $lastEvent->unit());
        $this->assertTrue($processPersisted->id()->equals($lastEvent->processId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = ProcessRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterProcessRequest(
            name: 'changedName',
            unit: Unit::Piece,
        );

        $result = $sut($request, UserMother::adminUser());

        $processPersisted = $repository->find($result->processId);

        $this->assertNotNull($processPersisted);
        $this->assertEquals('changedName', $processPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProcessTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($processPersisted->id()->equals($lastEvent->processId()));
    }

    public function testOwnerCannotRegister(): void
    {
        $repository = ProcessRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RegisterProcessRequest(name: 'test'), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testIsHandle(): void
    {
        $repository = ProcessRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterProcessRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ProcessRepository $repository, Locale $locale): RegisterProcessService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterProcessService(
            processRepository: $repository,
            authorizer: new ProcessAuthorizer(),
        );
    }
}
