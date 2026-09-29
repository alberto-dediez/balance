<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialitySector\Register;

use App\Materiality\Application\MaterialitySector\MaterialitySectorAuthorizer;
use App\Materiality\Application\MaterialitySector\Register\RegisterMaterialitySectorRequest;
use App\Materiality\Application\MaterialitySector\Register\RegisterMaterialitySectorService;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRegistered;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorTranslated;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterMaterialitySectorServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testUseCase(): void
    {
        $repository = MaterialitySectorRepositoryMother::inMemory();

        $result = $this->createSUT($repository, Locale::Spanish)(
            new RegisterMaterialitySectorRequest(name: 'Hoteles'),
            UserMother::adminUser(),
        );

        $this->assertSame('Hoteles', $result->name);

        $persisted = $repository->find($result->materialitySectorId);

        $this->assertNotNull($persisted);
        $this->assertSame('Hoteles', $persisted->defaultName());
        $this->assertCount(0, $persisted->translations());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MaterialitySectorRegistered::class, $lastEvent);
        $this->assertTrue($persisted->id()->equals($lastEvent->materialitySectorId()));
    }

    public function testRegisterWithLocaleTranslates(): void
    {
        $repository = MaterialitySectorRepositoryMother::inMemory();

        $result = $this->createSUT($repository, Locale::English)(
            new RegisterMaterialitySectorRequest(name: 'Hotels'),
            UserMother::adminUser(),
        );

        $persisted = $repository->find($result->materialitySectorId);

        $this->assertNotNull($persisted);
        $this->assertCount(1, $persisted->translations());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MaterialitySectorTranslated::class, $lastEvent);
        $this->assertSame(Locale::English, $lastEvent->locale());
    }

    public function testOwnerCannotRegister(): void
    {
        $repository = MaterialitySectorRepositoryMother::inMemory();

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository, Locale::Spanish)(
            new RegisterMaterialitySectorRequest(name: 'Hoteles'),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(MaterialitySectorRepositoryMother::inMemory(), Locale::Spanish);

        $this->assertTrue($sut->isHandled(new RegisterMaterialitySectorRequest()));
    }

    private function createSUT(MaterialitySectorRepository $repository, Locale $locale): RegisterMaterialitySectorService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterMaterialitySectorService(
            materialitySectorRepository: $repository,
            authorizer: new MaterialitySectorAuthorizer(),
        );
    }
}
