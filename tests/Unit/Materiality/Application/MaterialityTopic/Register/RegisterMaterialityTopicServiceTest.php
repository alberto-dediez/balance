<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityTopic\Register;

use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Application\MaterialityTopic\Register\RegisterMaterialityTopicRequest;
use App\Materiality\Application\MaterialityTopic\Register\RegisterMaterialityTopicService;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicNumberAlreadyUsedException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRegistered;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicTranslated;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
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

class RegisterMaterialityTopicServiceTest extends TestCase
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

        $result = $this->createSUT($repository, Locale::Spanish)(
            new RegisterMaterialityTopicRequest(
                scope: MaterialityScope::Scope3,
                direction: MaterialityTopicDirection::Upstream,
                name: 'Lavandería externa',
                description: 'Servicio de lavandería',
                position: 20,
            ),
            UserMother::adminUser(),
        );

        $this->assertSame('Lavandería externa', $result->name);

        $persisted = $repository->find($result->materialityTopicId);

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->number());
        $this->assertFalse($persisted->isStandard());
        $this->assertSame(20, $persisted->position());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MaterialityTopicRegistered::class, $lastEvent);
        $this->assertTrue($persisted->id()->equals($lastEvent->materialityTopicId()));
    }

    public function testDuplicatedNumberThrows(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();
        $repository->add(MaterialityTopicMother::standard(StandardScope3Category::BusinessTravel));

        $this->expectException(MaterialityTopicNumberAlreadyUsedException::class);

        $this->createSUT($repository, Locale::Spanish)(
            new RegisterMaterialityTopicRequest(number: 6, direction: MaterialityTopicDirection::Upstream, name: 'Duplicada'),
            UserMother::adminUser(),
        );
    }

    public function testOwnerCannotRegister(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository, Locale::Spanish)(
            new RegisterMaterialityTopicRequest(direction: MaterialityTopicDirection::Upstream, name: 'name'),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    public function testRegisterWithLocaleTranslates(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();

        $result = $this->createSUT($repository, Locale::English)(
            new RegisterMaterialityTopicRequest(direction: MaterialityTopicDirection::Upstream, name: 'English name', description: 'English description'),
            UserMother::adminUser(),
        );

        $persisted = $repository->find($result->materialityTopicId);

        $this->assertNotNull($persisted);
        $this->assertCount(1, $persisted->translations());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MaterialityTopicTranslated::class, $lastEvent);
        $this->assertSame(Locale::English, $lastEvent->locale());
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(MaterialityTopicRepositoryMother::inMemory(), Locale::Spanish);

        $this->assertTrue($sut->isHandled(new RegisterMaterialityTopicRequest()));
    }

    private function createSUT(MaterialityTopicRepository $repository, Locale $locale): RegisterMaterialityTopicService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterMaterialityTopicService(
            materialityTopicRepository: $repository,
            authorizer: new MaterialityTopicAuthorizer(),
        );
    }
}
