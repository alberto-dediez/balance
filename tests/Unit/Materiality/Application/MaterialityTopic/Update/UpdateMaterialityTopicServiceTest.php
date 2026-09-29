<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityTopic\Update;

use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Application\MaterialityTopic\Update\UpdateMaterialityTopicRequest;
use App\Materiality\Application\MaterialityTopic\Update\UpdateMaterialityTopicService;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicNotFoundException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicTranslated;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicUpdated;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateMaterialityTopicServiceTest extends TestCase
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
        $repository = MaterialityTopicRepositoryMother::inMemory();
        $materialityTopic = MaterialityTopicMother::random();
        $repository->add($materialityTopic);

        $result = $this->createSUT($repository, Locale::Spanish)(
            $this->request($materialityTopic->id(), 'Nuevo nombre'),
            UserMother::adminUser(),
        );

        $persisted = $repository->find($result->materialityTopicId);

        $this->assertNotNull($persisted);
        $this->assertSame('Nuevo nombre', $persisted->name());
        $this->assertSame(MaterialityTopicDirection::Downstream, $persisted->direction());
        $this->assertFalse($persisted->isActive());
        $this->assertInstanceOf(MaterialityTopicUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testNotFoundThrows(): void
    {
        $this->expectException(MaterialityTopicNotFoundException::class);

        $this->createSUT(MaterialityTopicRepositoryMother::inMemory(), Locale::Spanish)(
            $this->request(MaterialityTopicId::generate(), 'name'),
            UserMother::adminUser(),
        );
    }

    public function testOwnerCannotUpdate(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();
        $materialityTopic = MaterialityTopicMother::random();
        $repository->add($materialityTopic);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository, Locale::Spanish)(
            $this->request($materialityTopic->id(), 'name'),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();
        $materialityTopic = MaterialityTopicMother::fromName('Nombre base');
        $repository->add($materialityTopic);

        $result = $this->createSUT($repository, Locale::English)(
            $this->request($materialityTopic->id(), 'changedName'),
            UserMother::adminUser(),
        );

        $persisted = $repository->find($result->materialityTopicId);

        $this->assertNotNull($persisted);
        $this->assertSame('changedName', $persisted->name());
        $this->assertSame('Nombre base', $persisted->defaultName());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MaterialityTopicTranslated::class, $lastEvent);
        $this->assertTrue($persisted->id()->equals($lastEvent->materialityTopicId()));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(MaterialityTopicRepositoryMother::inMemory(), Locale::Spanish);

        $this->assertTrue($sut->isHandled($this->request(MaterialityTopicId::generate(), 'name')));
    }

    private function request(MaterialityTopicId $id, string $name): UpdateMaterialityTopicRequest
    {
        return new UpdateMaterialityTopicRequest(
            materialityTopicId: $id,
            scope: MaterialityScope::Scope3,
            direction: MaterialityTopicDirection::Downstream,
            name: $name,
            description: 'description',
            position: 4,
            active: false,
        );
    }

    private function createSUT(MaterialityTopicRepository $repository, Locale $locale): UpdateMaterialityTopicService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateMaterialityTopicService(
            materialityTopicRepository: $repository,
            authorizer: new MaterialityTopicAuthorizer(),
        );
    }
}
