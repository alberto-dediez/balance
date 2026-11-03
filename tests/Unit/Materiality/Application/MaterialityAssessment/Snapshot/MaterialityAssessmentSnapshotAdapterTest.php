<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment\Snapshot;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Materiality\Application\MaterialityAssessment\Snapshot\MaterialityAssessmentSnapshotAdapter;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialityDecisionRule;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

final class MaterialityAssessmentSnapshotAdapterTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    private const string FOOTPRINT_ID = 'footprint-id';

    private InMemoryUserRepository $users;
    private MaterialityTopic $topic;
    private MaterialityQuestion $magnitude;
    private MaterialityQuestion $outsourcing;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
        $this->users = new InMemoryUserRepository();
        $this->topic = MaterialityTopicMother::standard(StandardScope3Category::ProcessingOfSoldProducts);
        $this->magnitude = MaterialityQuestionMother::from('¿Qué magnitud?', 'question-magnitude', MaterialityCriterion::Magnitude, weight: 2);
        $this->outsourcing = MaterialityQuestionMother::from('¿Está externalizada?', 'question-outsourcing', MaterialityCriterion::Outsourcing, answerScale: MaterialityAnswerScale::YesNo);
        $this->materialityQuestions->add($this->magnitude);
        $this->materialityQuestions->add($this->outsourcing);
    }

    public function testWithoutAssessmentThereIsNoSnapshot(): void
    {
        $this->assertNull($this->adapter()->find(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID));
    }

    public function testACompletedAssessmentIsMappedWithItsScoresAndAnswers(): void
    {
        $user = UserMother::fromUsernameAndRole('jgarcia', Role::Owner);
        $this->users->add($user);
        $this->materialityAssessments->add($this->completedBy($user->id()));

        $snapshot = $this->adapter()->find(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID);

        $this->assertNotNull($snapshot);
        $this->assertTrue($snapshot->completed);
        $this->assertSame('jgarcia', $snapshot->completedByName);
        $this->assertSame(50, $snapshot->thresholdPercent);
        $this->assertNull($snapshot->highMagnitudeRule);
        $this->assertFalse($snapshot->questionSetChangedSince);
        $this->assertNotNull($snapshot->completedAt);

        $topic = $snapshot->topics[0];
        $this->assertSame((string) $this->topic->id(), $topic->topicId);
        $this->assertSame(10, $topic->topicNumber);
        $this->assertSame(3, $topic->points);
        $this->assertSame(3, $topic->minPoints);
        $this->assertSame(9, $topic->maxPoints);
        $this->assertSame(0.0, $topic->scorePercent);
        $this->assertSame(MaterialityVerdict::Omit, $topic->verdict);

        $answers = $snapshot->answersOf($topic->topicId);
        $this->assertCount(2, $answers);
        $this->assertSame(['magnitude', 'outsourcing'], array_map(static fn($answer): string => $answer->criterionKey, $answers));
        $this->assertSame(['level_1', 'yes_no_1'], array_map(static fn($answer): string => $answer->answerLabelKey, $answers));
        $this->assertSame(['level', 'yes_no'], array_map(static fn($answer): string => $answer->answerScaleKey, $answers));
        $this->assertSame([2, 1], array_map(static fn($answer): int => $answer->points, $answers));
        $this->assertFalse($answers[0]->questionChangedSince);
        $this->assertFalse($answers[0]->questionRemoved);
    }

    public function testTheHighMagnitudeRuleIsOnWhenACategoryWasForcedByIt(): void
    {
        $this->materialityAssessments->add($this->completedWithHighestMagnitude(new MaterialityDecisionRule(80, true)));

        $snapshot = $this->adapter()->find(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID);

        $this->assertNotNull($snapshot);
        $this->assertTrue($snapshot->topics[0]->forcedByHighMagnitude);
        $this->assertTrue($snapshot->highMagnitudeRule);
    }

    public function testTheHighMagnitudeRuleIsOffWhenACategoryOfHighestMagnitudeWasOmitted(): void
    {
        $this->materialityAssessments->add($this->completedWithHighestMagnitude(new MaterialityDecisionRule(80, false)));

        $snapshot = $this->adapter()->find(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID);

        $this->assertNotNull($snapshot);
        $this->assertSame(MaterialityVerdict::Omit, $snapshot->topics[0]->verdict);
        $this->assertFalse($snapshot->highMagnitudeRule);
    }

    public function testTheHighMagnitudeRuleIsUnknownWhenNoCategoryDependsOnIt(): void
    {
        $this->materialityAssessments->add($this->completedBy(UserId::generate()));

        $snapshot = $this->adapter()->find(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID);

        $this->assertNotNull($snapshot);
        $this->assertNull($snapshot->highMagnitudeRule);
    }

    public function testAQuestionChangedOrRemovedInTheMasterIsFlagged(): void
    {
        $this->materialityAssessments->add($this->completedBy(UserId::generate()));
        $this->magnitude->update(
            criterion: $this->magnitude->criterion(),
            topicId: $this->magnitude->topicId(),
            text: '¿Qué magnitud tienen?',
            help: $this->magnitude->help(),
            answerScale: $this->magnitude->answerScale(),
            weight: $this->magnitude->weight(),
            position: $this->magnitude->position(),
            active: true,
        );
        $this->materialityQuestions->remove($this->outsourcing);

        $snapshot = $this->adapter()->find(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID);

        $this->assertNotNull($snapshot);
        $answers = $snapshot->answersOf((string) $this->topic->id());
        $this->assertTrue($answers[0]->questionChangedSince);
        $this->assertFalse($answers[0]->questionRemoved);
        $this->assertTrue($answers[1]->questionRemoved);
        $this->assertTrue($snapshot->questionSetChangedSince);
        $this->assertNull($snapshot->completedByName);
    }

    public function testAReopenedAssessmentIsADraftWithoutThreshold(): void
    {
        $assessment = $this->completedBy(UserId::generate());
        $assessment->reopen();
        $this->materialityAssessments->add($assessment);

        $snapshot = $this->adapter()->find(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID);

        $this->assertNotNull($snapshot);
        $this->assertFalse($snapshot->completed);
        $this->assertNull($snapshot->thresholdPercent);
        $this->assertNull($snapshot->topics[0]->verdict);
    }

    public function testTheSnapshotIsLookedUpBySubjectType(): void
    {
        $this->materialityAssessments->add($this->completedBy(UserId::generate()));

        $this->assertNull($this->adapter()->find(CarbonFootprintReportSubjectType::Company, self::FOOTPRINT_ID));
    }

    private function completedBy(UserId $userId): MaterialityAssessment
    {
        $assessment = MaterialityAssessmentMother::answered(
            subjectType: MaterialityAssessmentSubjectType::Property,
            carbonFootprintId: self::FOOTPRINT_ID,
            topics: [$this->topic],
            questions: [$this->magnitude, $this->outsourcing],
            value: 1,
        );
        $assessment->complete($userId, MaterialityDecisionRule::default());

        return $assessment;
    }

    private function completedWithHighestMagnitude(MaterialityDecisionRule $rule): MaterialityAssessment
    {
        $assessment = MaterialityAssessmentMother::draft(
            subjectType: MaterialityAssessmentSubjectType::Property,
            carbonFootprintId: self::FOOTPRINT_ID,
            topics: [$this->topic],
            questions: [$this->magnitude, $this->outsourcing],
        );
        $topic = $assessment->topics()[0];
        $values = [];

        foreach ($assessment->answersOf($topic->topicId()) as $answer) {
            $values[(string) $answer->id()] = MaterialityCriterion::Magnitude === $answer->criterion() ? MaterialityAnswerScale::Level->max() : 1;
        }

        $assessment->answerTopic($topic->id(), $values);
        $assessment->complete(UserId::generate(), $rule);

        return $assessment;
    }

    private function adapter(): MaterialityAssessmentSnapshotAdapter
    {
        return new MaterialityAssessmentSnapshotAdapter($this->materialityAssessments, $this->materialityQuestions, $this->users);
    }
}
