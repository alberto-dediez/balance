<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialitySector\Update;

use App\Materiality\Application\MaterialitySector\MaterialitySectorAuthorizer;
use App\Materiality\Application\MaterialitySector\Update\UpdateMaterialitySectorRequest;
use App\Materiality\Application\MaterialitySector\Update\UpdateMaterialitySectorService;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorNotFoundException;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorUpdated;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateMaterialitySectorServiceTest extends TestCase
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
        $materialitySector = MaterialitySectorMother::fromName('Hoteles');
        $repository->add($materialitySector);

        $result = $this->createSUT($repository, Locale::Spanish)(
            new UpdateMaterialitySectorRequest(materialitySectorId: $materialitySector->id(), name: 'Alojamientos'),
            UserMother::adminUser(),
        );

        $this->assertSame('Alojamientos', $result->name);

        $persisted = $repository->find($materialitySector->id());

        $this->assertNotNull($persisted);
        $this->assertSame('Alojamientos', $persisted->defaultName());
        $this->assertInstanceOf(MaterialitySectorUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUpdateWithLocaleTranslatesAndKeepsBaseName(): void
    {
        $repository = MaterialitySectorRepositoryMother::inMemory();
        $materialitySector = MaterialitySectorMother::fromName('Hoteles');
        $repository->add($materialitySector);

        $this->createSUT($repository, Locale::English)(
            new UpdateMaterialitySectorRequest(materialitySectorId: $materialitySector->id(), name: 'Hotels'),
            UserMother::adminUser(),
        );

        $persisted = $repository->find($materialitySector->id());

        $this->assertNotNull($persisted);
        $this->assertSame('Hoteles', $persisted->defaultName());
        $this->assertSame('Hotels', $persisted->name());
    }

    public function testNotFoundThrows(): void
    {
        $this->expectException(MaterialitySectorNotFoundException::class);

        $this->createSUT(MaterialitySectorRepositoryMother::inMemory(), Locale::Spanish)(
            new UpdateMaterialitySectorRequest(materialitySectorId: MaterialitySectorId::generate(), name: 'Hoteles'),
            UserMother::adminUser(),
        );
    }

    public function testOwnerCannotUpdate(): void
    {
        $repository = MaterialitySectorRepositoryMother::inMemory();
        $materialitySector = MaterialitySectorMother::random();
        $repository->add($materialitySector);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository, Locale::Spanish)(
            new UpdateMaterialitySectorRequest(materialitySectorId: $materialitySector->id(), name: 'Hoteles'),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(MaterialitySectorRepositoryMother::inMemory(), Locale::Spanish);

        $this->assertTrue($sut->isHandled(new UpdateMaterialitySectorRequest(materialitySectorId: MaterialitySectorId::generate(), name: 'name')));
    }

    private function createSUT(MaterialitySectorRepository $repository, Locale $locale): UpdateMaterialitySectorService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateMaterialitySectorService(
            materialitySectorRepository: $repository,
            authorizer: new MaterialitySectorAuthorizer(),
        );
    }
}
