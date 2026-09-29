<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialityQuestion\InvalidMaterialityQuestionWeightException;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRegistered;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRemoved;
use App\Materiality\Domain\Model\MaterialityQuestion\StandardMaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MaterialityQuestionTest extends TestCase
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

    public function testRegisterPublishesEvent(): void
    {
        $topicId = MaterialityTopicId::generate();
        $materialityQuestion = new MaterialityQuestion(MaterialityQuestionId::generate());

        $materialityQuestion->register(MaterialityCriterion::Risk, $topicId, 'Texto', 'Ayuda', MaterialityAnswerScale::YesNo, 2, 3);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MaterialityQuestionRegistered::class, $lastEvent);
        $this->assertSame((string) $topicId, $lastEvent->payload()['topicId']);
        $this->assertSame('yes_no', $lastEvent->payload()['answerScale']);
        $this->assertSame(2, $materialityQuestion->weight());
        $this->assertFalse($materialityQuestion->isGeneric());
    }

    public function testZeroWeightThrows(): void
    {
        $materialityQuestion = new MaterialityQuestion(MaterialityQuestionId::generate());

        $this->expectException(InvalidMaterialityQuestionWeightException::class);

        $materialityQuestion->register(MaterialityCriterion::Risk, null, 'Texto', '', MaterialityAnswerScale::Level, 0, 0);
    }

    public function testUpdateWithZeroWeightThrows(): void
    {
        $materialityQuestion = MaterialityQuestionMother::random();

        $this->expectException(InvalidMaterialityQuestionWeightException::class);

        $materialityQuestion->update(MaterialityCriterion::Risk, null, 'Texto', '', MaterialityAnswerScale::Level, 0, 0, true);
    }

    public function testIsGeneric(): void
    {
        $this->assertTrue(MaterialityQuestionMother::random()->isGeneric());
        $this->assertFalse(MaterialityQuestionMother::forTopic(MaterialityTopicId::generate())->isGeneric());
    }

    public function testTranslateTextAndHelp(): void
    {
        $materialityQuestion = MaterialityQuestionMother::fromText('Texto base');

        $materialityQuestion->update(MaterialityCriterion::Influence, null, 'English text', 'English help', MaterialityAnswerScale::Level, 1, 1, true, locale: Locale::English);

        $this->assertSame('Texto base', $materialityQuestion->text());
        $this->assertSame('Texto base', $materialityQuestion->defaultText());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('English text', $materialityQuestion->text());
        $this->assertSame('English help', $materialityQuestion->help());
    }

    public function testRemoveTwicePublishesOneEvent(): void
    {
        $materialityQuestion = MaterialityQuestionMother::random();

        $materialityQuestion->remove();
        $materialityQuestion->remove();

        $removedEvents = array_filter(
            $this->spySubscriber->events(),
            static fn($event) => $event instanceof MaterialityQuestionRemoved,
        );

        $this->assertCount(1, $removedEvents);
        $this->assertTrue($materialityQuestion->isRemoved());
    }

    public function testAnswerScaleValues(): void
    {
        $this->assertSame([1, 2, 3], MaterialityAnswerScale::Level->values());
        $this->assertSame(3, MaterialityAnswerScale::Level->max());
        $this->assertSame([1, 3], MaterialityAnswerScale::YesNo->values());
        $this->assertSame(3, MaterialityAnswerScale::YesNo->max());
    }

    public function testStandardMaterialityQuestions(): void
    {
        $cases = StandardMaterialityQuestion::cases();

        $this->assertCount(5, $cases);
        $this->assertCount(5, array_unique(array_map(static fn(StandardMaterialityQuestion $case) => $case->criterion()->value, $cases)));

        foreach ($cases as $case) {
            $this->assertNotEmpty($case->text(Locale::Spanish));
            $this->assertNotEmpty($case->text(Locale::English));
            $this->assertNotEmpty($case->help(Locale::Spanish));
            $this->assertNotEmpty($case->help(Locale::English));
            $this->assertNull($case->text(Locale::French));
            $this->assertGreaterThanOrEqual(1, $case->weight());
        }

        $this->assertSame(MaterialityAnswerScale::YesNo, StandardMaterialityQuestion::Outsourcing->answerScale());
        $this->assertSame(2, StandardMaterialityQuestion::Magnitude->weight());
    }
}
