<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityQuestion\Update;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Application\MaterialityQuestion\Update\UpdateMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Update\UpdateMaterialityQuestionService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionNotFoundException;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionSectorUnlinked;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionTranslated;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionTranslation;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionUpdated;
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
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepositoryMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepositoryMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateMaterialityQuestionServiceTest extends TestCase
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

    public function testUseCase(): void
    {
        $topic = MaterialityTopicMother::random();
        $this->topicRepository->add($topic);
        $materialityQuestion = MaterialityQuestionMother::random();
        $this->repository->add($materialityQuestion);

        $this->createSUT(Locale::Spanish)(
            $this->request($materialityQuestion->id(), 'Nuevo texto', $topic->id()),
            UserMother::adminUser(),
        );

        $this->assertSame('Nuevo texto', $materialityQuestion->text());
        $this->assertEquals($topic->id(), $materialityQuestion->topicId());
        $this->assertSame(MaterialityCriterion::Outsourcing, $materialityQuestion->criterion());
        $this->assertSame(3, $materialityQuestion->weight());
        $this->assertFalse($materialityQuestion->isActive());
        $this->assertInstanceOf(MaterialityQuestionUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testNotFoundThrows(): void
    {
        $this->expectException(MaterialityQuestionNotFoundException::class);

        $this->createSUT(Locale::Spanish)(
            $this->request(MaterialityQuestionId::generate(), 'texto'),
            UserMother::adminUser(),
        );
    }

    public function testUnknownTopicThrows(): void
    {
        $materialityQuestion = MaterialityQuestionMother::random();
        $this->repository->add($materialityQuestion);

        $this->expectException(MaterialityTopicNotFoundException::class);

        $this->createSUT(Locale::Spanish)(
            $this->request($materialityQuestion->id(), 'texto', MaterialityTopicId::generate()),
            UserMother::adminUser(),
        );
    }

    public function testUpdateUnlinksRemovedSector(): void
    {
        $hotels = MaterialitySectorMother::fromName('Hoteles');
        $events = MaterialitySectorMother::fromName('Eventos');
        $this->sectorRepository->add($hotels);
        $this->sectorRepository->add($events);
        $materialityQuestion = MaterialityQuestionMother::forSectors([$hotels->id(), $events->id()]);
        $this->repository->add($materialityQuestion);

        $this->createSUT(Locale::Spanish)(
            $this->request($materialityQuestion->id(), 'texto', sectorIds: [$events->id()]),
            UserMother::adminUser(),
        );

        $this->assertEquals([$events->id()], $materialityQuestion->sectorIds());

        $unlinkedEvents = array_values(array_filter(
            $this->spySubscriber->events(),
            static fn($event) => $event instanceof MaterialityQuestionSectorUnlinked,
        ));

        $this->assertCount(1, $unlinkedEvents);
        $this->assertTrue($hotels->id()->equals($unlinkedEvents[0]->sectorId()));
    }

    public function testUnknownSectorThrows(): void
    {
        $materialityQuestion = MaterialityQuestionMother::random();
        $this->repository->add($materialityQuestion);

        $this->expectException(MaterialitySectorNotFoundException::class);

        $this->createSUT(Locale::Spanish)(
            $this->request($materialityQuestion->id(), 'texto', sectorIds: [MaterialitySectorId::generate()]),
            UserMother::adminUser(),
        );
    }

    public function testOwnerCannotUpdate(): void
    {
        $materialityQuestion = MaterialityQuestionMother::random();
        $this->repository->add($materialityQuestion);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT(Locale::Spanish)(
            $this->request($materialityQuestion->id(), 'texto'),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $materialityQuestion = MaterialityQuestionMother::fromText('Texto base');
        $this->repository->add($materialityQuestion);

        $this->createSUT(Locale::English)(
            $this->request($materialityQuestion->id(), 'English text'),
            UserMother::adminUser(),
        );

        $this->assertSame('English text', $materialityQuestion->text());
        $this->assertSame('Texto base', $materialityQuestion->defaultText());
        $this->assertInstanceOf(MaterialityQuestionTranslated::class, $this->spySubscriber->lastEvent());
    }

    public function testUpdateInNonDefaultLocaleKeepsBaseMarkdownHelp(): void
    {
        $materialityQuestion = MaterialityQuestionMother::fromText('Texto base');
        $this->repository->add($materialityQuestion);

        $this->createSUT(Locale::English)(
            $this->request($materialityQuestion->id(), 'English text', help: "- **High**: largest sources.\n- **Low**: marginal."),
            UserMother::adminUser(),
        );

        $this->assertSame('Texto base help', $materialityQuestion->defaultHelp());

        $translation = $materialityQuestion->translations()->getTranslationFor(Locale::English);

        $this->assertInstanceOf(MaterialityQuestionTranslation::class, $translation);
        $this->assertSame("- **High**: largest sources.\n- **Low**: marginal.", $translation->help());

        FakeLocaleSetter::reset();
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(Locale::Spanish);

        $this->assertTrue($sut->isHandled($this->request(MaterialityQuestionId::generate(), 'text')));
    }

    /** @param list<MaterialitySectorId> $sectorIds */
    private function request(MaterialityQuestionId $id, string $text, ?MaterialityTopicId $topicId = null, array $sectorIds = [], string $help = 'help'): UpdateMaterialityQuestionRequest
    {
        return new UpdateMaterialityQuestionRequest(
            materialityQuestionId: $id,
            criterion: MaterialityCriterion::Outsourcing,
            topicId: $topicId,
            text: $text,
            help: $help,
            answerScale: MaterialityAnswerScale::YesNo,
            weight: 3,
            position: 2,
            active: false,
            sectorIds: $sectorIds,
        );
    }

    private function createSUT(Locale $locale): UpdateMaterialityQuestionService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateMaterialityQuestionService(
            materialityQuestionRepository: $this->repository,
            materialityTopicRepository: $this->topicRepository,
            materialitySectorRepository: $this->sectorRepository,
            authorizer: new MaterialityQuestionAuthorizer(),
        );
    }
}
