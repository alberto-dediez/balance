<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\Seed;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Application\MaterialityQuestion\Register\RegisterMaterialityQuestionService;
use App\Materiality\Application\MaterialityQuestion\Translate\TranslateMaterialityQuestionService;
use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Application\MaterialityTopic\Register\RegisterMaterialityTopicService;
use App\Materiality\Application\MaterialityTopic\Translate\TranslateMaterialityTopicService;
use App\Materiality\Application\Seed\SeedStandardMaterialityMastersRequest;
use App\Materiality\Application\Seed\SeedStandardMaterialityMastersService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionFilter;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicFilter;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityQuestionRepository;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialitySectorRepository;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityTopicRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepositoryMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class SeedStandardMaterialityMastersServiceTest extends TestCase
{
    private InMemoryMaterialityTopicRepository $topicRepository;

    private InMemoryMaterialityQuestionRepository $questionRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        new SpyDomainEventSubscriber();

        $this->topicRepository = MaterialityTopicRepositoryMother::inMemory();
        $this->questionRepository = MaterialityQuestionRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testSeedsTopicsAndQuestionsWithTranslations(): void
    {
        $response = $this->createSUT('es|en')(new SeedStandardMaterialityMastersRequest(), UserMother::adminUser());

        $this->assertCount(15, $response->registeredTopics);
        $this->assertSame(0, $response->skippedTopics);
        $this->assertCount(5, $response->registeredQuestions);
        $this->assertFalse($response->questionsSkipped);

        foreach ($response->registeredTopics as $topic) {
            $this->assertTrue($topic->isStandard());
            $this->assertTrue($topic->isActive());
            $this->assertSame(MaterialityScope::Scope3, $topic->scope());
            $this->assertSame($topic->number(), $topic->position());
            $this->assertCount(1, $topic->translations());
            $this->assertNotNull($topic->translations()->getTranslationFor(Locale::English));
        }

        foreach ($response->registeredQuestions as $question) {
            $this->assertTrue($question->isGeneric());
            $this->assertTrue($question->appliesToAllSectors());
            $this->assertCount(1, $question->translations());
        }

        $businessTravel = $this->topicRepository->findByNumber(StandardScope3Category::BusinessTravel->value);

        $this->assertNotNull($businessTravel);
        $this->assertSame('Viajes de negocios', $businessTravel->name());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('Business travel', $businessTravel->name());
    }

    public function testDoesNotTranslateToLocalesWithoutText(): void
    {
        $response = $this->createSUT('es|en|fr')(new SeedStandardMaterialityMastersRequest(), UserMother::adminUser());

        foreach ($response->registeredTopics as $topic) {
            $this->assertCount(1, $topic->translations());
            $this->assertNull($topic->translations()->getTranslationFor(Locale::French));
        }
    }

    public function testDoesNotTranslateWithOnlyDefaultLocale(): void
    {
        $response = $this->createSUT('es')(new SeedStandardMaterialityMastersRequest(), UserMother::adminUser());

        foreach ($response->registeredTopics as $topic) {
            $this->assertCount(0, $topic->translations());
        }

        foreach ($response->registeredQuestions as $question) {
            $this->assertCount(0, $question->translations());
        }
    }

    public function testSecondRunRegistersNothing(): void
    {
        $sut = $this->createSUT('es|en');
        $sut(new SeedStandardMaterialityMastersRequest(), UserMother::adminUser());

        $response = $sut(new SeedStandardMaterialityMastersRequest(), UserMother::adminUser());

        $this->assertCount(0, $response->registeredTopics);
        $this->assertSame(15, $response->skippedTopics);
        $this->assertCount(0, $response->registeredQuestions);
        $this->assertTrue($response->questionsSkipped);
        $this->assertSame(15, $this->topicRepository->totalResultsByFilter(new MaterialityTopicFilter(standard: true)));
        $this->assertSame(5, $this->questionRepository->totalResultsByFilter(new MaterialityQuestionFilter()));
    }

    public function testRegistersOnlyMissingTopics(): void
    {
        $this->topicRepository->add(MaterialityTopicMother::standard(StandardScope3Category::BusinessTravel));

        $response = $this->createSUT('es|en')(new SeedStandardMaterialityMastersRequest(), UserMother::adminUser());

        $this->assertCount(14, $response->registeredTopics);
        $this->assertSame(1, $response->skippedTopics);
    }

    public function testDoesNotSeedQuestionsWhenOneExists(): void
    {
        $this->questionRepository->add(MaterialityQuestionMother::random());

        $response = $this->createSUT('es|en')(new SeedStandardMaterialityMastersRequest(), UserMother::adminUser());

        $this->assertCount(0, $response->registeredQuestions);
        $this->assertTrue($response->questionsSkipped);
        $this->assertSame(1, $this->questionRepository->totalResultsByFilter(new MaterialityQuestionFilter()));
    }

    public function testOwnerCannotSeed(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT('es|en')(new SeedStandardMaterialityMastersRequest(), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testSeedingWhileBrowsingInEnglishDoesNotDuplicateTranslations(): void
    {
        FakeLocaleSetter::set(Locale::English);

        $response = $this->createSUT('es|en')(new SeedStandardMaterialityMastersRequest(), UserMother::adminUser());

        $this->assertCount(15, $response->registeredTopics);
        $this->assertCount(5, $response->registeredQuestions);

        foreach ($response->registeredTopics as $topic) {
            $this->assertCount(1, $topic->translations());
            $this->assertSame((string) StandardScope3Category::from((int) $topic->number())->name(Locale::English), $topic->name());
            $this->assertSame((string) StandardScope3Category::from((int) $topic->number())->name(Locale::Spanish), $topic->defaultName());
        }

        foreach ($response->registeredQuestions as $question) {
            $this->assertCount(1, $question->translations());
        }
    }

    private function createSUT(string $localeList): SeedStandardMaterialityMastersService
    {
        $topicAuthorizer = new MaterialityTopicAuthorizer();
        $questionAuthorizer = new MaterialityQuestionAuthorizer();

        return new SeedStandardMaterialityMastersService(
            materialityTopicRepository: $this->topicRepository,
            materialityQuestionRepository: $this->questionRepository,
            authorizer: $topicAuthorizer,
            registerMaterialityTopic: new RegisterMaterialityTopicService($this->topicRepository, $topicAuthorizer),
            translateMaterialityTopic: new TranslateMaterialityTopicService($this->topicRepository, $topicAuthorizer),
            registerMaterialityQuestion: new RegisterMaterialityQuestionService($this->questionRepository, $this->topicRepository, new InMemoryMaterialitySectorRepository(), $questionAuthorizer),
            translateMaterialityQuestion: new TranslateMaterialityQuestionService($this->questionRepository, $questionAuthorizer),
            localeList: $localeList,
        );
    }
}
