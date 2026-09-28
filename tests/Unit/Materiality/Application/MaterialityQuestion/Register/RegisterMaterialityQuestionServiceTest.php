<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityQuestion\Register;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Application\MaterialityQuestion\Register\RegisterMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Register\RegisterMaterialityQuestionService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRegistered;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionTranslated;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicNotFoundException;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityQuestionRepository;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityTopicRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepositoryMother;
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

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->repository = MaterialityQuestionRepositoryMother::inMemory();
        $this->topicRepository = MaterialityTopicRepositoryMother::inMemory();
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

    private function createSUT(Locale $locale): RegisterMaterialityQuestionService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterMaterialityQuestionService(
            materialityQuestionRepository: $this->repository,
            materialityTopicRepository: $this->topicRepository,
            authorizer: new MaterialityQuestionAuthorizer(),
        );
    }
}
