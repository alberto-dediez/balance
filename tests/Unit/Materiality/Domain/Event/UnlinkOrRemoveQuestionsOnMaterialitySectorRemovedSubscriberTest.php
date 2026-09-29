<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Event;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Application\MaterialityQuestion\Remove\RemoveMaterialityQuestionService;
use App\Materiality\Application\MaterialityQuestion\Update\UpdateMaterialityQuestionService;
use App\Materiality\Domain\Event\UnlinkOrRemoveQuestionsOnMaterialitySectorRemovedSubscriber;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionTranslation;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRemoved;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRemoved;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityQuestionRepository;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialitySectorRepository;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityTopicRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UnlinkOrRemoveQuestionsOnMaterialitySectorRemovedSubscriberTest extends TestCase
{
    private InMemoryMaterialityQuestionRepository $repository;

    private InMemoryMaterialitySectorRepository $sectorRepository;

    private MaterialitySector $sectorA;

    private MaterialitySector $sectorB;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->repository = new InMemoryMaterialityQuestionRepository();
        $this->sectorRepository = new InMemoryMaterialitySectorRepository();

        $this->sectorA = MaterialitySectorMother::fromName('Hoteles');
        $this->sectorB = MaterialitySectorMother::fromName('Eventos');
        $this->sectorRepository->add($this->sectorA);
        $this->sectorRepository->add($this->sectorB);
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testIsSubscribedToMaterialitySectorRemoved(): void
    {
        $this->assertTrue($this->createSUT()->isSubscribedTo(new MaterialitySectorRemoved(MaterialitySectorId::generate())));
        $this->assertFalse($this->createSUT()->isSubscribedTo(new MaterialityTopicRemoved(MaterialityTopicId::generate())));
    }

    public function testUnlinksOrRemovesQuestionsOfTheRemovedSector(): void
    {
        $q1 = MaterialityQuestionMother::forSectors([$this->sectorA->id(), $this->sectorB->id()], 'q1');
        $q2 = MaterialityQuestionMother::forSectors([$this->sectorA->id()], 'q2');
        $q3 = MaterialityQuestionMother::fromText('q3');
        $q4 = MaterialityQuestionMother::forSectors([$this->sectorB->id()], 'q4');

        foreach ([$q1, $q2, $q3, $q4] as $materialityQuestion) {
            $this->repository->add($materialityQuestion);
        }

        $this->createSUT()->handle(new MaterialitySectorRemoved($this->sectorA->id()));

        $this->assertFalse($q1->isRemoved());
        $this->assertEquals([$this->sectorB->id()], $q1->sectorIds());
        $this->assertTrue($q2->isRemoved());
        $this->assertFalse($q3->isRemoved());
        $this->assertTrue($q3->appliesToAllSectors());
        $this->assertFalse($q4->isRemoved());
        $this->assertEquals([$this->sectorB->id()], $q4->sectorIds());
    }

    public function testKeepsTextsWhenLoggedUserBrowsesInAnotherLocale(): void
    {
        $q1 = MaterialityQuestionMother::forSectors([$this->sectorA->id(), $this->sectorB->id()], 'Texto base');
        $q1->translate('English text', 'English help', Locale::English);
        $this->repository->add($q1);

        FakeLocaleSetter::set(Locale::English);

        $this->createSUT()->handle(new MaterialitySectorRemoved($this->sectorA->id()));

        $this->assertEquals([$this->sectorB->id()], $q1->sectorIds());
        $this->assertSame('Texto base', $q1->defaultText());
        $this->assertSame('Texto base help', $q1->defaultHelp());

        $translation = $q1->translations()->getTranslationFor(Locale::English);

        $this->assertInstanceOf(MaterialityQuestionTranslation::class, $translation);
        $this->assertSame('English text', $translation->text());
        $this->assertSame('English help', $translation->help());
    }

    private function createSUT(): UnlinkOrRemoveQuestionsOnMaterialitySectorRemovedSubscriber
    {
        $authorizer = new MaterialityQuestionAuthorizer();

        return new UnlinkOrRemoveQuestionsOnMaterialitySectorRemovedSubscriber(
            $this->repository,
            new UpdateMaterialityQuestionService($this->repository, new InMemoryMaterialityTopicRepository(), $this->sectorRepository, $authorizer),
            new RemoveMaterialityQuestionService($this->repository, $authorizer),
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
