<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionFilter;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MaterialityQuestionRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
        parent::tearDown();
    }

    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();

        $materialityQuestion = MaterialityQuestionMother::registeredMaterialityQuestion();

        $this->assertNull($repository->find($materialityQuestion->id()));

        $repository->add($materialityQuestion);

        $recovered = $repository->find($materialityQuestion->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($materialityQuestion->id(), $recovered->id());
    }

    public function testRemove(): void
    {
        $repository = $this->createSUT();

        $materialityQuestion = MaterialityQuestionMother::registeredMaterialityQuestion();

        $repository->add($materialityQuestion);
        $repository->remove($materialityQuestion);

        $this->assertNull($repository->find($materialityQuestion->id()));
    }

    public function testFindDoesNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $materialityQuestion = MaterialityQuestionMother::registeredMaterialityQuestion();

        $repository->add($materialityQuestion);

        $materialityQuestion->remove();

        $repository->add($materialityQuestion);

        $this->assertNull($repository->find($materialityQuestion->id()));
        $this->assertSame(0, $repository->totalResultsByFilter(new MaterialityQuestionFilter()));
    }

    public function testFindByTopicId(): void
    {
        $repository = $this->createSUT();

        $topicId = MaterialityTopicId::generate();

        $repository->add(MaterialityQuestionMother::forTopic($topicId));
        $repository->add(MaterialityQuestionMother::forTopic($topicId));
        $repository->add(MaterialityQuestionMother::forTopic(MaterialityTopicId::generate()));
        $repository->add(MaterialityQuestionMother::random());

        $this->assertCount(2, $repository->findByTopicId($topicId));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $topicId = MaterialityTopicId::generate();

        $repository->add(MaterialityQuestionMother::from(text: 'Pregunta de riesgo', id: (string) MaterialityQuestionId::generate(), criterion: MaterialityCriterion::Risk));
        $repository->add(MaterialityQuestionMother::from(text: 'Pregunta de influencia', id: (string) MaterialityQuestionId::generate(), criterion: MaterialityCriterion::Influence));
        $repository->add(MaterialityQuestionMother::forTopic($topicId, 'Pregunta específica', MaterialityCriterion::Risk));

        $inactive = MaterialityQuestionMother::random();
        $inactive->update(MaterialityCriterion::Other, null, $inactive->text(), '', MaterialityAnswerScale::Level, 1, 0, false);
        $repository->add($inactive);

        $this->assertCount(4, $repository->findByFilter(new MaterialityQuestionFilter()));
        $this->assertCount(1, $repository->findByFilter(new MaterialityQuestionFilter(text: 'influencia')));
        $this->assertCount(2, $repository->findByFilter(new MaterialityQuestionFilter(criterion: MaterialityCriterion::Risk)));
        $this->assertCount(1, $repository->findByFilter(new MaterialityQuestionFilter(topicId: $topicId)));
        $this->assertCount(3, $repository->findByFilter(new MaterialityQuestionFilter(onlyGeneric: true)));
        $this->assertCount(3, $repository->findByFilter(new MaterialityQuestionFilter(active: true)));
        $this->assertCount(1, $repository->findByFilter(new MaterialityQuestionFilter(active: false)));
        $this->assertSame(4, $repository->totalResultsByFilter(new MaterialityQuestionFilter(limit: 1)));
    }

    public function testFindByFilterWithTextTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $materialityQuestion = MaterialityQuestionMother::registeredMaterialityQuestion();
        $materialityQuestion->translate('translated-text', 'translated-help', Locale::English);

        $repository->add($materialityQuestion);
        $repository->add(MaterialityQuestionMother::random());

        $recovered = $repository->findByFilter(new MaterialityQuestionFilter(text: 'translated-text'));

        $this->assertCount(1, $recovered);
        $this->assertSame('translated-text', $recovered[0]->text());
        $this->assertSame('translated-help', $recovered[0]->help());
    }

    public function testDefaultOrderIsByPosition(): void
    {
        $repository = $this->createSUT();

        $repository->add(MaterialityQuestionMother::from(text: 'C', id: (string) MaterialityQuestionId::generate(), position: 3));
        $repository->add(MaterialityQuestionMother::from(text: 'A', id: (string) MaterialityQuestionId::generate(), position: 1));
        $repository->add(MaterialityQuestionMother::from(text: 'B', id: (string) MaterialityQuestionId::generate(), position: 2));

        $texts = array_map(
            static fn($materialityQuestion) => $materialityQuestion->text(),
            $repository->findByFilter(new MaterialityQuestionFilter()),
        );

        $this->assertSame(['A', 'B', 'C'], $texts);
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): MaterialityQuestionRepository;
}
