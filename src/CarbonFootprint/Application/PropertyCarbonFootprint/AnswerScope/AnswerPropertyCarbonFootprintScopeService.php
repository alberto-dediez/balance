<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerScope;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialityOverrideReconciler;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AnswerPropertyCarbonFootprintScopeService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
        private StageAnswersProcessor $answersProcessor,
        private StageAnswersSynchronizer $answersSynchronizer,
        private MaterialityOverrideReconciler $overrideReconciler,
    ) {}

    public function __invoke(AnswerPropertyCarbonFootprintScopeRequest $request, User $user): void
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint) {
            throw new \InvalidArgumentException('PropertyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($propertyCarbonFootprint, SecurityAction::Edit, $user);

        $sections = $request->scope->sectionsFor($propertyCarbonFootprint->isPrecalculated());

        $validated = $this->answersProcessor->validate($request->scope, $request->sectionAnswers, $request->exclusions, $request->stageExclusion, $sections);

        $this->overrideReconciler->validate($propertyCarbonFootprint, $request->scope, $validated->exclusionsBySource, $validated->stageExclusion, $request->materialityOverrideJustifications);

        $this->answersProcessor->applyStageExclusion($propertyCarbonFootprint, $request->scope, $validated->stageExclusion);

        if (null === $validated->stageExclusion) {
            $this->answersProcessor->applyExclusions($propertyCarbonFootprint, $request->scope, $validated->exclusionsBySource);

            $this->overrideReconciler->reconcile($propertyCarbonFootprint, $request->scope, $user->id(), $request->materialityOverrideJustifications);

            $this->answersSynchronizer->sync($propertyCarbonFootprint, $request->scope, $validated->sectionAnswers, $user, $sections);
        }

        $propertyCarbonFootprint->answerScope($request->scope);

        $this->propertyCarbonFootprintRepository->add($propertyCarbonFootprint);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof AnswerPropertyCarbonFootprintScopeRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AnswerPropertyCarbonFootprintScopeRequest;
    }
}
