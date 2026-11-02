<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\Start;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentAuthorizer;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentFootprints;
use App\Materiality\Application\MaterialityAssessment\MaterialityQuestionnaire;
use App\Materiality\Application\MaterialityAssessment\MaterialityQuestionnaireBuilder;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAlreadyExistsException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorNotFoundException;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class StartMaterialityAssessmentService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityAssessmentRepository $materialityAssessmentRepository,
        private MaterialitySectorRepository $materialitySectorRepository,
        private MaterialityAssessmentFootprints $footprints,
        private MaterialityQuestionnaireBuilder $questionnaireBuilder,
        private MaterialityAssessmentAuthorizer $authorizer,
    ) {}

    public function __invoke(StartMaterialityAssessmentRequest $request, User $user): StartMaterialityAssessmentResponse
    {
        $carbonFootprint = $this->footprints->find($request->subjectType, $request->carbonFootprintId);

        $this->footprints->denyUnlessCanDoThat($request->subjectType, $carbonFootprint, SecurityAction::Edit, $user);

        $this->footprints->ensureIsEditable($carbonFootprint);

        $this->footprints->ensureScope2Answered($carbonFootprint);

        if (null !== $this->materialityAssessmentRepository->findCurrentByCarbonFootprint($request->subjectType, $request->carbonFootprintId)) {
            throw new MaterialityAssessmentAlreadyExistsException($request->subjectType, $request->carbonFootprintId);
        }

        $sectorName = null;

        if (null !== $request->sectorId) {
            $sector = $this->materialitySectorRepository->find($request->sectorId);

            if (!$sector instanceof MaterialitySector) {
                throw new MaterialitySectorNotFoundException($request->sectorId);
            }

            $sectorName = $sector->defaultName();
        }

        $questionnaire = $this->questionnaireBuilder->build($request->sectorId);

        $initialValues = $request->copyPreviousAnswers ? $this->previousValues($request, $carbonFootprint, $questionnaire) : [];

        $accountId = $carbonFootprint->accountId();

        if (!$accountId instanceof AccountId) {
            throw new \RuntimeException('CarbonFootprint has no account');
        }

        $materialityAssessment = new MaterialityAssessment(MaterialityAssessmentId::generate());
        $materialityAssessment->start(
            subjectType: $request->subjectType,
            carbonFootprintId: $request->carbonFootprintId,
            accountId: $accountId,
            year: $carbonFootprint->calculationYear(),
            sectorId: $request->sectorId,
            sectorName: $sectorName,
            topics: $questionnaire->topics,
            questions: $questionnaire->questions,
            initialValues: $initialValues,
        );

        $this->authorizer->denyUnlessCanDoThat($materialityAssessment, SecurityAction::Register, $user);

        $this->materialityAssessmentRepository->add($materialityAssessment);

        return new StartMaterialityAssessmentResponse(
            materialityAssessmentId: $materialityAssessment->id(),
            firstTopicId: ($materialityAssessment->topics()[0] ?? null)?->id(),
        );
    }

    /** @return array<string, int> */
    private function previousValues(StartMaterialityAssessmentRequest $request, CarbonFootprint $carbonFootprint, MaterialityQuestionnaire $questionnaire): array
    {
        $previousCarbonFootprint = $this->footprints->previous($request->subjectType, $carbonFootprint);

        if (null === $previousCarbonFootprint) {
            return [];
        }

        $previous = $this->materialityAssessmentRepository->findCurrentByCarbonFootprint($request->subjectType, (string) $previousCarbonFootprint->id());

        if (null === $previous || !$previous->isCompleted()) {
            return [];
        }

        $scales = [];

        foreach ($questionnaire->questions as $question) {
            $scales[$question->key()] = $question->answerScale;
        }

        $values = [];

        foreach ($previous->answers() as $answer) {
            $key = sprintf('%s|%s', (string) $answer->topicId(), (string) $answer->questionId());

            if (null === $answer->value() || ($scales[$key] ?? null) !== $answer->answerScale()) {
                continue;
            }

            $values[$key] = $answer->value();
        }

        return $values;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof StartMaterialityAssessmentRequest;
    }
}
