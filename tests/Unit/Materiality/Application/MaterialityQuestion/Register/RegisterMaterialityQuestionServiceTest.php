<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityQuestion\Register;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Application\MaterialityQuestion\Register\RegisterMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Register\RegisterMaterialityQuestionService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionFilter;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRegistered;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionSectorLinked;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionTranslated;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorNotFoundException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicNotFoundException;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityQuestionRepository;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialitySectorRepository;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityTopicRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepositoryMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepositoryMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterMaterialityQuestionServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private InMemoryMaterialityQuestionRepository $repository;

    private InMemoryMaterialityTopicRepository $topicRepository;

    private InMemoryMaterialitySectorRepository $sectorRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->repository = MaterialityQuestionRepositoryMother::inMemory();
        $this->topicRepository = MaterialityTopicRepositoryMother::inMemory();
        $this->sectorRepository = MaterialitySectorRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testRegistersGenericQuestion(): void
    {
        $result = $this->createSUT(Locale::Spanish)(
            new RegisterMaterialityQuestionRequest(
                criterion: MaterialityCriterion::Risk,
                text: '¿Pregunta?',
                help: 'Ayuda',
                answerScale: MaterialityAnswerScale::YesNo,
                weight: 2,
                position: 7,
            ),
            UserMother::adminUser(),
        );

        $persisted = $this->repository->find($result->materialityQuestionId);

        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->isGeneric());
        $this->assertSame('¿Pregunta?', $result->text);
        $this->assertInstanceOf(MaterialityQuestionRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testRegistersSpecificQuestion(): void
    {
        $topic = MaterialityTopicMother::random();
        $this->topicRepository->add($topic);

        $result = $this->createSUT(Locale::Spanish)(
            new RegisterMaterialityQuestionRequest(topicId: $topic->id(), text: '¿Pregunta?'),
            UserMother::adminUser(),
        );

        $persisted = $this->repository->find($result->materialityQuestionId);

        $this->assertNotNull($persisted);
        $this->assertEquals($topic->id(), $persisted->topicId());
    }

    public function testUnknownTopicThrows(): void
    {
        $this->expectException(MaterialityTopicNotFoundException::class);

        $this->createSUT(Locale::Spanish)(
            new RegisterMaterialityQuestionRequest(topicId: MaterialityTopicId::generate(), text: '¿Pregunta?'),
            UserMother::adminUser(),
        );
    }

    public function testRegistersQuestionForSectors(): void
    {
        $hotels = MaterialitySectorMother::fromName('Hoteles');
        $events = MaterialitySectorMother::fromName('Eventos');
        $this->sectorRepository->add($hotels);
        $this->sectorRepository->add($events);

        $result = $this->createSUT(Locale::Spanish)(
            new RegisterMaterialityQuestionRequest(text: '¿Pregunta?', sectorIds: [$hotels->id(), $events->id()]),
            UserMother::adminUser(),
        );

        $persisted = $this->repository->find($result->materialityQuestionId);

        $this->assertNotNull($persisted);
        $this->assertEquals([$hotels->id(), $events->id()], $persisted->sectorIds());
        $this->assertFalse($persisted->appliesToAllSectors());

        $linkedEvents = array_filter(
            $this->spySubscriber->events(),
            static fn($event) => $event instanceof MaterialityQuestionSectorLinked,
        );

        $this->assertCount(2, $linkedEvents);
    }

    public function testRegistersQuestionForAllSectorsWithoutSectors(): void
    {
        $result = $this->createSUT(Locale::Spanish)(
            new RegisterMaterialityQuestionRequest(text: '¿Pregunta?'),
            UserMother::adminUser(),
        );

        $persisted = $this->repository->find($result->materialityQuestionId);

        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->appliesToAllSectors());
    }

    public function testUnknownSectorThrowsAndDoesNotPersist(): void
    {
        $hotels = MaterialitySectorMother::fromName('Hoteles');
        $this->sectorRepository->add($hotels);

        try {
            $this->createSUT(Locale::Spanish)(
                new RegisterMaterialityQuestionRequest(text: '¿Pregunta?', sectorIds: [$hotels->id(), MaterialitySectorId::generate()]),
                UserMother::adminUser(),
            );

            $this->fail('MaterialitySectorNotFoundException expected');
        } catch (MaterialitySectorNotFoundException) {
            $this->assertSame(0, $this->repository->totalResultsByFilter(new MaterialityQuestionFilter()));
        }
    }

    public function testOwnerCannotRegister(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT(Locale::Spanish)(
            new RegisterMaterialityQuestionRequest(text: '¿Pregunta?'),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    public function testRegisterWithLocaleTranslates(): void
    {
        $this->createSUT(Locale::English)(
            new RegisterMaterialityQuestionRequest(text: 'Question?', help: 'Help'),
            UserMother::adminUser(),
        );

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MaterialityQuestionTranslated::class, $lastEvent);
        $this->assertSame(Locale::English, $lastEvent->locale());
    }

    public function testRegisterKeepsMarkdownHelpVerbatim(): void
    {
        $help = "**Alto** significa que la categoría es previsiblemente una de las mayores fuentes de emisiones de la organización.\n\n"
            . "- **Medio**: relevante, pero no entre las principales.\n"
            . "- **Bajo**: marginal frente al resto de categorías.\n\n"
            . 'Consulta la [guía del GHG Protocol](https://ghgprotocol.org/scope-3-calculation-guidance) para estimar la magnitud.';

        $result = $this->createSUT(Locale::Spanish)(
            new RegisterMaterialityQuestionRequest(text: '¿Pregunta?', help: $help),
            UserMother::adminUser(),
        );

        $persisted = $this->repository->find($result->materialityQuestionId);

        $this->assertGreaterThan(255, mb_strlen($help));
        $this->assertNotNull($persisted);
        $this->assertSame($help, $persisted->defaultHelp());
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(Locale::Spanish);

        $this->assertTrue($sut->isHandled(new RegisterMaterialityQuestionRequest()));
    }

    private function createSUT(Locale $locale): RegisterMaterialityQuestionService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterMaterialityQuestionService(
            materialityQuestionRepository: $this->repository,
            materialityTopicRepository: $this->topicRepository,
            materialitySectorRepository: $this->sectorRepository,
            authorizer: new MaterialityQuestionAuthorizer(),
        );
    }
}
