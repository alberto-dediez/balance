<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Materiality;

use App\Materiality\Application\MaterialityAssessment\Complete\CompleteMaterialityAssessmentRequest;
use App\Materiality\Application\MaterialityAssessment\Complete\CompleteMaterialityAssessmentService;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentFootprints;
use App\Materiality\Application\MaterialityAssessment\MaterialityQuestionnaireBuilder;
use App\Materiality\Application\Seed\SeedStandardMaterialityMastersRequest;
use App\Materiality\Application\Seed\SeedStandardMaterialityMastersService;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAnswer;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentFilter;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentStatus;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionFilter;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionTranslation;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorFilter;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorTranslation;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicFilter;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicTranslation;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Acceptance\Shared\CsrfTokenInSessionTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

final class MaterialityContext implements Context
{
    use CsrfTokenInSessionTrait;

    private AcceptanceClient $client;

    public function __construct(KernelBrowser $kernelBrowser)
    {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    private function getTopicRepository(): MaterialityTopicRepository
    {
        return $this->client->getContainer()->get(MaterialityTopicRepository::class);
    }

    private function getQuestionRepository(): MaterialityQuestionRepository
    {
        return $this->client->getContainer()->get(MaterialityQuestionRepository::class);
    }

    private function getSectorRepository(): MaterialitySectorRepository
    {
        return $this->client->getContainer()->get(MaterialitySectorRepository::class);
    }

    private function getAssessmentRepository(): MaterialityAssessmentRepository
    {
        return $this->client->getContainer()->get(MaterialityAssessmentRepository::class);
    }

    private function getSettingsRepository(): MaterialitySettingsRepository
    {
        return $this->client->getContainer()->get(MaterialitySettingsRepository::class);
    }

    #[Given('materiality settings with threshold :percent exist')]
    public function materialitySettingsWithThresholdExist(int $percent): void
    {
        $this->getSettingsRepository()->add(MaterialitySettingsMother::withThreshold($percent));
    }

    #[Then('materiality settings should have threshold :percent and high magnitude rule :enabled')]
    public function materialitySettingsShouldHaveThresholdAndHighMagnitudeRule(int $percent, string $enabled): void
    {
        $materialitySettings = $this->getSettingsRepository()->findCurrent();

        Assert::assertNotNull($materialitySettings);
        Assert::assertSame($percent, $materialitySettings->relevanceThresholdPercent());
        Assert::assertSame('yes' === $enabled, $materialitySettings->highMagnitudeForcesInclusion());
    }

    #[Then('materiality settings should not exist')]
    public function materialitySettingsShouldNotExist(): void
    {
        Assert::assertNull($this->getSettingsRepository()->findCurrent());
    }

    #[Given('materiality topic :name with id :id exists')]
    public function materialityTopicExists(string $name, string $id): void
    {
        $this->getTopicRepository()->add(MaterialityTopicMother::fromNameAndId($name, $id));
    }

    #[Given('materiality topic :name with id :id in scope :scope exists')]
    public function materialityTopicInScopeExists(string $name, string $id, string $scope): void
    {
        $materialityScope = MaterialityScope::from($scope);

        $this->getTopicRepository()->add(MaterialityTopicMother::from(
            name: $name,
            id: $id,
            scope: $materialityScope,
            direction: MaterialityScope::Scope3 === $materialityScope ? MaterialityTopicDirection::Upstream : null,
        ));
    }

    #[Given('standard materiality topic :number with id :id exists')]
    public function standardMaterialityTopicExists(string $number, string $id): void
    {
        $this->getTopicRepository()->add(MaterialityTopicMother::standard(StandardScope3Category::from((int) $number), $id));
    }

    #[Given('materiality topic :id is inactive')]
    public function materialityTopicIsInactive(string $id): void
    {
        $materialityTopic = $this->topic($id);

        $materialityTopic->update(
            scope: $materialityTopic->scope(),
            direction: $materialityTopic->direction(),
            name: $materialityTopic->defaultName(),
            description: $materialityTopic->defaultDescription(),
            position: $materialityTopic->position(),
            active: false,
        );

        $this->getTopicRepository()->add($materialityTopic);
    }

    #[Given('materiality question :text with id :id for criterion :criterion exists')]
    public function materialityQuestionExists(string $text, string $id, string $criterion): void
    {
        $this->getQuestionRepository()->add(MaterialityQuestionMother::from(
            text: $text,
            id: $id,
            criterion: MaterialityCriterion::from($criterion),
        ));
    }

    #[Given('materiality question :text with id :id for criterion :criterion and topic :topicId exists')]
    public function materialityQuestionForTopicExists(string $text, string $id, string $criterion, string $topicId): void
    {
        $this->getQuestionRepository()->add(MaterialityQuestionMother::from(
            text: $text,
            id: $id,
            criterion: MaterialityCriterion::from($criterion),
            topicId: new MaterialityTopicId($topicId),
        ));
    }

    #[Given('materiality question :text with id :id for criterion :criterion and sectors :sectorIds exists')]
    public function materialityQuestionForSectorsExists(string $text, string $id, string $criterion, string $sectorIds): void
    {
        $this->getQuestionRepository()->add(MaterialityQuestionMother::from(
            text: $text,
            id: $id,
            criterion: MaterialityCriterion::from($criterion),
            sectorIds: array_map(
                static fn(string $sectorId): MaterialitySectorId => new MaterialitySectorId(trim($sectorId)),
                explode(',', $sectorIds),
            ),
        ));
    }

    #[Given('materiality question :text with id :id for criterion :criterion, topic :topicId and sectors :sectorIds exists')]
    public function materialityQuestionForTopicAndSectorsExists(string $text, string $id, string $criterion, string $topicId, string $sectorIds): void
    {
        $this->getQuestionRepository()->add(MaterialityQuestionMother::from(
            text: $text,
            id: $id,
            criterion: MaterialityCriterion::from($criterion),
            topicId: new MaterialityTopicId($topicId),
            answerScale: MaterialityAnswerScale::YesNo,
            position: 10,
            sectorIds: array_map(
                static fn(string $sectorId): MaterialitySectorId => new MaterialitySectorId(trim($sectorId)),
                explode(',', $sectorIds),
            ),
        ));
    }

    #[Given('materiality question :text with id :id for criterion :criterion with scale :scale, weight :weight and position :position exists')]
    public function materialityQuestionWithScaleWeightAndPositionExists(string $text, string $id, string $criterion, string $scale, int $weight, int $position): void
    {
        $this->getQuestionRepository()->add(MaterialityQuestionMother::from(
            text: $text,
            id: $id,
            criterion: MaterialityCriterion::from($criterion),
            answerScale: MaterialityAnswerScale::from($scale),
            weight: $weight,
            position: $position,
        ));
    }

    #[Given('materiality question :text with id :id for criterion :criterion with scale :scale, weight :weight, position :position and topic :topicId exists')]
    public function materialityQuestionWithScaleWeightPositionAndTopicExists(string $text, string $id, string $criterion, string $scale, int $weight, int $position, string $topicId): void
    {
        $this->getQuestionRepository()->add(MaterialityQuestionMother::from(
            text: $text,
            id: $id,
            criterion: MaterialityCriterion::from($criterion),
            topicId: new MaterialityTopicId($topicId),
            answerScale: MaterialityAnswerScale::from($scale),
            weight: $weight,
            position: $position,
        ));
    }

    #[Given('materiality sector :name with id :id exists')]
    public function materialitySectorExists(string $name, string $id): void
    {
        $this->getSectorRepository()->add(MaterialitySectorMother::fromNameAndId($name, $id));
    }

    #[Given('standard materiality masters are seeded')]
    public function standardMaterialityMastersAreSeeded(): void
    {
        $service = $this->client->getContainer()->get(SeedStandardMaterialityMastersService::class);

        $service(new SeedStandardMaterialityMastersRequest(), UserMother::adminUser());
    }

    #[Then('materiality topic :id should be removed')]
    public function materialityTopicShouldBeRemoved(string $id): void
    {
        Assert::assertTrue($this->topic($id, includeRemoved: true)->isRemoved());
    }

    #[Then('materiality topic :id should have name :name in locale :locale')]
    public function materialityTopicShouldHaveNameInLocale(string $id, string $name, string $locale): void
    {
        $materialityTopic = $this->topic($id);
        $expectedLocale = Locale::from($locale);

        if (LocaleGetter::default() === $expectedLocale) {
            Assert::assertSame($name, $materialityTopic->defaultName());

            return;
        }

        $translation = $materialityTopic->translations()->getTranslationFor($expectedLocale);

        Assert::assertInstanceOf(MaterialityTopicTranslation::class, $translation);
        Assert::assertSame($name, $translation->name());
    }

    #[Then('materiality topic :id should have description :description in locale :locale')]
    public function materialityTopicShouldHaveDescriptionInLocale(string $id, string $description, string $locale): void
    {
        $materialityTopic = $this->topic($id);
        $expectedLocale = Locale::from($locale);

        if (LocaleGetter::default() === $expectedLocale) {
            Assert::assertSame($description, $materialityTopic->defaultDescription());

            return;
        }

        $translation = $materialityTopic->translations()->getTranslationFor($expectedLocale);

        Assert::assertInstanceOf(MaterialityTopicTranslation::class, $translation);
        Assert::assertSame($description, $translation->description());
    }

    #[Then('materiality topic with name :name should have description :description')]
    public function materialityTopicWithNameShouldHaveDescription(string $name, string $description): void
    {
        Assert::assertSame($description, $this->topicByName($name)->defaultDescription());
    }

    #[Then('materiality sector :id should be removed')]
    public function materialitySectorShouldBeRemoved(string $id): void
    {
        Assert::assertTrue($this->sector($id, includeRemoved: true)->isRemoved());
    }

    #[Then('materiality sector :id should have name :name in locale :locale')]
    public function materialitySectorShouldHaveNameInLocale(string $id, string $name, string $locale): void
    {
        $materialitySector = $this->sector($id);
        $expectedLocale = Locale::from($locale);

        if (LocaleGetter::default() === $expectedLocale) {
            Assert::assertSame($name, $materialitySector->defaultName());

            return;
        }

        $translation = $materialitySector->translations()->getTranslationFor($expectedLocale);

        Assert::assertInstanceOf(MaterialitySectorTranslation::class, $translation);
        Assert::assertSame($name, $translation->name());
    }

    #[Then('there should be :count materiality sectors')]
    public function thereShouldBeMaterialitySectors(int $count): void
    {
        Assert::assertSame($count, $this->getSectorRepository()->totalResultsByFilter(new MaterialitySectorFilter()));
    }

    #[Then('materiality question :id should be removed')]
    public function materialityQuestionShouldBeRemoved(string $id): void
    {
        Assert::assertTrue($this->question($id)->isRemoved());
    }

    #[Then('materiality question :id should have help :help in locale :locale')]
    public function materialityQuestionShouldHaveHelpInLocale(string $id, string $help, string $locale): void
    {
        $materialityQuestion = $this->question($id);
        $expectedLocale = Locale::from($locale);

        if (LocaleGetter::default() === $expectedLocale) {
            Assert::assertSame($help, $materialityQuestion->defaultHelp());

            return;
        }

        $translation = $materialityQuestion->translations()->getTranslationFor($expectedLocale);

        Assert::assertInstanceOf(MaterialityQuestionTranslation::class, $translation);
        Assert::assertSame($help, $translation->help());
    }

    #[Then('materiality question with text :text should have help :help')]
    public function materialityQuestionWithTextShouldHaveHelp(string $text, string $help): void
    {
        Assert::assertSame($help, $this->questionByText($text)->defaultHelp());
    }

    #[Then('materiality question :id should have topic :topicId')]
    public function materialityQuestionShouldHaveTopic(string $id, string $topicId): void
    {
        Assert::assertSame($topicId, (string) $this->question($id)->topicId());
    }

    #[Then('materiality question :id should have sectors :sectorIds')]
    public function materialityQuestionShouldHaveSectors(string $id, string $sectorIds): void
    {
        $expected = array_map('trim', explode(',', $sectorIds));
        $actual = array_map(
            static fn(MaterialitySectorId $sectorId): string => (string) $sectorId,
            $this->question($id)->sectorIds(),
        );

        sort($expected);
        sort($actual);

        Assert::assertSame($expected, $actual);
    }

    #[Then('materiality question :id should apply to all sectors')]
    public function materialityQuestionShouldApplyToAllSectors(string $id): void
    {
        Assert::assertTrue($this->question($id)->appliesToAllSectors());
    }

    #[Then('there should be :count standard materiality topics')]
    public function thereShouldBeStandardMaterialityTopics(int $count): void
    {
        Assert::assertSame($count, $this->getTopicRepository()->totalResultsByFilter(new MaterialityTopicFilter(standard: true)));
    }

    #[Then('there should be :count materiality questions')]
    public function thereShouldBeMaterialityQuestions(int $count): void
    {
        Assert::assertSame($count, $this->getQuestionRepository()->totalResultsByFilter(new MaterialityQuestionFilter()));
    }

    #[Given('materiality assessment with id :id for :subjectType carbon footprint :carbonFootprintId of account :account exists')]
    public function materialityAssessmentExists(string $id, string $subjectType, string $carbonFootprintId, string $account): void
    {
        $this->startAssessment($id, $subjectType, $carbonFootprintId, $account, null);
    }

    #[Given('materiality assessment with id :id for :subjectType carbon footprint :carbonFootprintId of account :account with sector :sectorId exists')]
    public function materialityAssessmentWithSectorExists(string $id, string $subjectType, string $carbonFootprintId, string $account, string $sectorId): void
    {
        $this->startAssessment($id, $subjectType, $carbonFootprintId, $account, new MaterialitySectorId($sectorId));
    }

    #[Given('materiality assessment :id has all questions answered with value :value')]
    public function materialityAssessmentHasAllQuestionsAnsweredWithValue(string $id, int $value): void
    {
        $assessment = $this->assessment($id);

        foreach ($assessment->topics() as $topic) {
            $values = [];

            foreach ($assessment->answersOf($topic->topicId()) as $answer) {
                $values[(string) $answer->id()] = $value;
            }

            $assessment->answerTopic($topic->id(), $values);
        }

        $this->getAssessmentRepository()->add($assessment);
    }

    #[Given('materiality assessment :id has value :value for question :questionId in topic :topicId')]
    public function materialityAssessmentHasValueForQuestionInTopic(string $id, int $value, string $questionId, string $topicId): void
    {
        $assessment = $this->assessment($id);
        $answer = $this->answer($assessment, $questionId, $topicId);

        foreach ($assessment->topics() as $topic) {
            if ($topic->topicId()->equals($answer->topicId())) {
                $assessment->answerTopic($topic->id(), [(string) $answer->id() => $value]);
            }
        }

        $this->getAssessmentRepository()->add($assessment);
    }

    #[Given('materiality assessment :id is completed')]
    public function materialityAssessmentIsCompleted(string $id): void
    {
        $this->materialityAssessmentHasAllQuestionsAnsweredWithValue($id, 3);

        $assessment = $this->assessment($id);
        $accountId = $assessment->accountId();
        Assert::assertInstanceOf(AccountId::class, $accountId);

        $service = $this->client->getContainer()->get(CompleteMaterialityAssessmentService::class);
        $service(
            new CompleteMaterialityAssessmentRequest($assessment->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner-' . $accountId, Role::Owner, $accountId),
        );
    }

    #[When('I submit the materiality topic form with value :value for all questions')]
    public function iSubmitTheMaterialityTopicFormWithValueForAllQuestions(string $value): void
    {
        $crawler = $this->client->getCrawler();
        $form = $crawler->filter('form[name="materiality_topic"]');
        Assert::assertCount(1, $form);

        $token = $form->filter('input[name="_token"]')->attr('value');
        Assert::assertNotNull($token);

        $answers = [];

        foreach ($form->filter('input[type="radio"]') as $radio) {
            assert($radio instanceof \DOMElement);

            if (1 === preg_match('/^answers\[(.+)\]$/', $radio->getAttribute('name'), $matches)) {
                $answers[$matches[1]] = $value;
            }
        }

        $uri = $this->client->getRequest()->getUri();

        $this->client->requestWithFiles('POST', $uri, ['_token' => $token, 'answers' => $answers], [], ['HTTP_REFERER' => $uri]);
    }

    #[When('I skip the materiality step')]
    public function iSkipTheMaterialityStep(): void
    {
        if ($this->client->getResponse()->isRedirection()) {
            $this->client->followRedirect();
        }

        $form = $this->client->getCrawler()->filter('form[name="materiality_skip"]');
        Assert::assertCount(1, $form, 'Materiality skip form not found');

        $this->client->submit($form->form());
    }

    #[When('I start the materiality assessment with sector :sectorId')]
    public function iStartTheMaterialityAssessmentWithSector(string $sectorId): void
    {
        $this->submitStartForm(['start_materiality_assessment[sectorId]' => $sectorId], copyPreviousAnswers: false);
    }

    #[When('I start the materiality assessment without sector')]
    public function iStartTheMaterialityAssessmentWithoutSector(): void
    {
        $this->submitStartForm([], copyPreviousAnswers: false);
    }

    #[When('I start the materiality assessment copying the previous answers')]
    public function iStartTheMaterialityAssessmentCopyingThePreviousAnswers(): void
    {
        $this->submitStartForm([], copyPreviousAnswers: true);
    }

    #[Then('materiality assessment :id should be completed')]
    public function materialityAssessmentShouldBeCompleted(string $id): void
    {
        Assert::assertSame(MaterialityAssessmentStatus::Completed, $this->assessment($id)->status());
    }

    #[Then('materiality assessment :id should be draft')]
    public function materialityAssessmentShouldBeDraft(string $id): void
    {
        Assert::assertSame(MaterialityAssessmentStatus::Draft, $this->assessment($id)->status());
    }

    #[Then('materiality assessment :id should be removed')]
    public function materialityAssessmentShouldBeRemoved(string $id): void
    {
        Assert::assertTrue($this->assessment($id, includeRemoved: true)->isRemoved());
    }

    #[Then('materiality assessment :id should have score :percent for topic :topicId')]
    public function materialityAssessmentShouldHaveScoreForTopic(string $id, string $percent, string $topicId): void
    {
        foreach ($this->assessment($id)->topics() as $topic) {
            if ((string) $topic->topicId() === $topicId) {
                Assert::assertSame('---' === $percent ? null : (float) str_replace(',', '.', $percent), $topic->scorePercent());

                return;
            }
        }

        throw new \RuntimeException(sprintf('Topic "%s" not found in materiality assessment "%s"', $topicId, $id));
    }

    #[Then('materiality assessment :id should have :count answers for topic :topicId')]
    public function materialityAssessmentShouldHaveAnswersForTopic(string $id, int $count, string $topicId): void
    {
        Assert::assertCount($count, $this->assessment($id)->answersOf(new MaterialityTopicId($topicId)));
    }

    #[Then('materiality assessment :id should have :count answered questions')]
    public function materialityAssessmentShouldHaveAnsweredQuestions(string $id, int $count): void
    {
        Assert::assertSame($count, $this->assessment($id)->answeredCount());
    }

    #[Then('materiality assessment :id should keep question :questionId text :text')]
    public function materialityAssessmentShouldKeepQuestionText(string $id, string $questionId, string $text): void
    {
        $answers = array_filter(
            $this->assessment($id)->answers(),
            static fn(MaterialityAssessmentAnswer $answer): bool => (string) $answer->questionId() === $questionId,
        );

        Assert::assertNotEmpty($answers);

        foreach ($answers as $answer) {
            Assert::assertSame($text, $answer->text());
        }
    }

    #[Then('materiality assessment for :subjectType carbon footprint :carbonFootprintId should not exist')]
    public function materialityAssessmentForCarbonFootprintShouldNotExist(string $subjectType, string $carbonFootprintId): void
    {
        Assert::assertNull($this->getAssessmentRepository()->findCurrentByCarbonFootprint(MaterialityAssessmentSubjectType::from($subjectType), $carbonFootprintId));
    }

    #[Then('materiality assessment for :subjectType carbon footprint :carbonFootprintId should have :count answered questions')]
    public function materialityAssessmentForCarbonFootprintShouldHaveAnsweredQuestions(string $subjectType, string $carbonFootprintId, int $count): void
    {
        $assessment = $this->getAssessmentRepository()->findCurrentByCarbonFootprint(MaterialityAssessmentSubjectType::from($subjectType), $carbonFootprintId);

        Assert::assertNotNull($assessment);
        Assert::assertSame($count, $assessment->answeredCount());
    }

    #[Then('materiality assessment for :subjectType carbon footprint :carbonFootprintId should have :count answers for topic :topicId')]
    public function materialityAssessmentForCarbonFootprintShouldHaveAnswersForTopic(string $subjectType, string $carbonFootprintId, int $count, string $topicId): void
    {
        $assessment = $this->getAssessmentRepository()->findCurrentByCarbonFootprint(MaterialityAssessmentSubjectType::from($subjectType), $carbonFootprintId);

        Assert::assertNotNull($assessment);
        Assert::assertCount($count, $assessment->answersOf(new MaterialityTopicId($topicId)));
    }

    #[When('I go to the materiality assessment :id topic :topicId')]
    public function iGoToTheMaterialityAssessmentTopic(string $id, string $topicId): void
    {
        foreach ($this->assessment($id)->topics() as $topic) {
            if ((string) $topic->topicId() === $topicId) {
                $this->client->request('GET', sprintf('/es/materiality-assessments/%s/topics/%s', $id, $topic->id()));

                return;
            }
        }

        throw new \RuntimeException(sprintf('Topic "%s" not found in materiality assessment "%s"', $topicId, $id));
    }

    #[When('I post to the materiality assessment :id action :action')]
    public function iPostToTheMaterialityAssessmentAction(string $id, string $action): void
    {
        $form = $this->client->getCrawler()->filter(sprintf('form[action$="/materiality-assessments/%s/%s"]', $id, $action));
        Assert::assertCount(1, $form, sprintf('Form for action "%s" not found', $action));

        $this->client->submit($form->form());
    }

    #[When('I post to the materiality assessment :id action :action with a valid token')]
    public function iPostToTheMaterialityAssessmentActionWithAValidToken(string $id, string $action): void
    {
        $token = $this->csrfTokenInCurrentSession(sprintf('materiality_assessment_%s_%s', $action, $id));

        $this->client->requestWithFiles('POST', sprintf('/es/materiality-assessments/%s/%s', $id, $action), ['_token' => $token]);
    }

    #[When('I post the start of the materiality assessment of :subjectType carbon footprint :carbonFootprintId')]
    public function iPostTheStartOfTheMaterialityAssessmentOfCarbonFootprint(string $subjectType, string $carbonFootprintId): void
    {
        $this->client->requestWithFiles(
            'POST',
            sprintf('/es/materiality-assessments/%s/%s/start', $subjectType, $carbonFootprintId),
            ['start_materiality_assessment' => ['_token' => 'csrf-token']],
            [],
            ['HTTP_ORIGIN' => 'http://localhost'],
        );
    }

    private function startAssessment(string $id, string $subjectType, string $carbonFootprintId, string $account, ?MaterialitySectorId $sectorId): void
    {
        $type = MaterialityAssessmentSubjectType::from($subjectType);
        $footprint = $this->client->getContainer()->get(MaterialityAssessmentFootprints::class)->find($type, $carbonFootprintId);
        $questionnaire = $this->client->getContainer()->get(MaterialityQuestionnaireBuilder::class)->build($sectorId);
        $sector = null !== $sectorId ? $this->getSectorRepository()->find($sectorId) : null;

        $assessment = new MaterialityAssessment(new MaterialityAssessmentId($id));
        $assessment->start(
            subjectType: $type,
            carbonFootprintId: $carbonFootprintId,
            accountId: new AccountId($account),
            year: $footprint->calculationYear(),
            sectorId: $sectorId,
            sectorName: $sector?->defaultName(),
            topics: $questionnaire->topics,
            questions: $questionnaire->questions,
        );

        $this->getAssessmentRepository()->add($assessment);
    }

    /** @param array<string, string> $values */
    private function submitStartForm(array $values, bool $copyPreviousAnswers): void
    {
        $button = $this->client->getCrawler()->selectButton('Comenzar cuestionario');
        Assert::assertCount(1, $button, 'Start button not found');

        $form = $button->form();

        foreach ($values as $name => $value) {
            $form[$name] = $value;
        }

        if ($form->has('start_materiality_assessment[copyPreviousAnswers]')) {
            $field = $form['start_materiality_assessment[copyPreviousAnswers]'];
            Assert::assertInstanceOf(ChoiceFormField::class, $field);
            $copyPreviousAnswers ? $field->tick() : $field->untick();
        }

        $this->client->submit($form);
    }

    private function assessment(string $id, bool $includeRemoved = false): MaterialityAssessment
    {
        foreach ($this->getAssessmentRepository()->findByFilter(new MaterialityAssessmentFilter(includeRemoved: $includeRemoved, limit: 0)) as $assessment) {
            if ((string) $assessment->id() === $id) {
                return $assessment;
            }
        }

        throw new \RuntimeException(sprintf('Materiality assessment "%s" not found', $id));
    }

    private function answer(MaterialityAssessment $assessment, string $questionId, string $topicId): MaterialityAssessmentAnswer
    {
        foreach ($assessment->answersOf(new MaterialityTopicId($topicId)) as $answer) {
            if ($answer->questionId()->equals(new MaterialityQuestionId($questionId))) {
                return $answer;
            }
        }

        throw new \RuntimeException(sprintf('Question "%s" not found in topic "%s"', $questionId, $topicId));
    }

    private function topic(string $id, bool $includeRemoved = false): MaterialityTopic
    {
        foreach ($this->getTopicRepository()->findByFilter(new MaterialityTopicFilter(includeRemoved: $includeRemoved, limit: 0)) as $materialityTopic) {
            if ((string) $materialityTopic->id() === $id) {
                return $materialityTopic;
            }
        }

        throw new \RuntimeException(sprintf('Materiality topic "%s" not found', $id));
    }

    private function topicByName(string $name): MaterialityTopic
    {
        foreach ($this->getTopicRepository()->findByFilter(new MaterialityTopicFilter(includeRemoved: true, limit: 0)) as $materialityTopic) {
            if ($materialityTopic->defaultName() === $name) {
                return $materialityTopic;
            }
        }

        throw new \RuntimeException(sprintf('Materiality topic with name "%s" not found', $name));
    }

    private function sector(string $id, bool $includeRemoved = false): MaterialitySector
    {
        foreach ($this->getSectorRepository()->findByFilter(new MaterialitySectorFilter(includeRemoved: $includeRemoved, limit: 0)) as $materialitySector) {
            if ((string) $materialitySector->id() === $id) {
                return $materialitySector;
            }
        }

        throw new \RuntimeException(sprintf('Materiality sector "%s" not found', $id));
    }

    private function question(string $id): MaterialityQuestion
    {
        foreach ($this->getQuestionRepository()->findByFilter(new MaterialityQuestionFilter(includeRemoved: true, limit: 0)) as $materialityQuestion) {
            if ((string) $materialityQuestion->id() === $id) {
                return $materialityQuestion;
            }
        }

        throw new \RuntimeException(sprintf('Materiality question "%s" not found', $id));
    }

    private function questionByText(string $text): MaterialityQuestion
    {
        foreach ($this->getQuestionRepository()->findByFilter(new MaterialityQuestionFilter(includeRemoved: true, limit: 0)) as $materialityQuestion) {
            if ($materialityQuestion->defaultText() === $text) {
                return $materialityQuestion;
            }
        }

        throw new \RuntimeException(sprintf('Materiality question with text "%s" not found', $text));
    }
}
