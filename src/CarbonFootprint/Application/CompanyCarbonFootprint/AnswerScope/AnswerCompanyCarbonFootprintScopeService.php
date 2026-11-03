<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialityOverrideReconciler;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertiesSynchronizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AnswerCompanyCarbonFootprintScopeService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyProperties $companyProperties,
        private CompanyCarbonFootprintAuthorizer $authorizer,
        private StageAnswersProcessor $answersProcessor,
        private StageAnswersSynchronizer $answersSynchronizer,
        private NonConsolidatedPropertiesSynchronizer $nonConsolidatedPropertiesSynchronizer,
        private MaterialityOverrideReconciler $overrideReconciler,
    ) {}

    public function __invoke(AnswerCompanyCarbonFootprintScopeRequest $request, User $user): void
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::Edit, $user);

        $companyId = $companyCarbonFootprint->companyId();

        $companyPropertyIds = [];
        foreach ($this->companyProperties->consolidated($companyId) as $property) {
            $companyPropertyIds[(string) $property->id()] = $property->id();
        }

        $answersByPropertyKey = [];
        foreach ($request->answersByProperty as $block) {
            $propertyId = $block->propertyId;

            if ($propertyId instanceof PropertyId && !isset($companyPropertyIds[(string) $propertyId])) {
                throw new \InvalidArgumentException('Property does not belong to company');
            }

            $answersByPropertyKey[(string) $propertyId] = $block;
        }

        $sections = $request->scope->sectionsFor($companyCarbonFootprint->isPrecalculated());

        $validatedExclusions = $this->answersProcessor->validate($request->scope, [], $request->exclusions, $request->stageExclusion, $sections);

        if (null !== $validatedExclusions->stageExclusion) {
            foreach ($answersByPropertyKey as $block) {
                $this->answersProcessor->validate($request->scope, $block->sectionAnswers, [], $validatedExclusions->stageExclusion, $sections);
            }
        }

        $this->overrideReconciler->validate($companyCarbonFootprint, $request->scope, $validatedExclusions->exclusionsBySource, $validatedExclusions->stageExclusion, $request->materialityOverrideJustifications);

        $this->answersProcessor->applyStageExclusion($companyCarbonFootprint, $request->scope, $validatedExclusions->stageExclusion);

        if (null === $validatedExclusions->stageExclusion) {
            $this->answersProcessor->applyExclusions($companyCarbonFootprint, $request->scope, $validatedExclusions->exclusionsBySource);

            $this->overrideReconciler->reconcile($companyCarbonFootprint, $request->scope, $user->id(), $request->materialityOverrideJustifications);

            $blocks = ['' => null];
            foreach ($companyPropertyIds as $key => $propertyId) {
                $blocks[$key] = $propertyId;
            }

            foreach ($blocks as $key => $propertyId) {
                $block = $answersByPropertyKey[$key] ?? null;

                $validated = $this->answersProcessor->validate($request->scope, null !== $block ? $block->sectionAnswers : [], $request->exclusions, sections: $sections);

                $this->answersSynchronizer->sync($companyCarbonFootprint, $request->scope, $validated->sectionAnswers, $user, sections: $sections, propertyId: $propertyId);
            }

            foreach ($this->companyProperties->nonConsolidated($companyId) as $property) {
                $this->answersSynchronizer->sync($companyCarbonFootprint, $request->scope, [], $user, sections: $sections, propertyId: $property->id());
            }

            if (Scope::Scope3 === $request->scope) {
                $this->nonConsolidatedPropertiesSynchronizer->sync($companyCarbonFootprint, $request->nonConsolidatedProperties);
            }
        }

        $companyCarbonFootprint->answerScope($request->scope);

        $this->companyCarbonFootprintRepository->add($companyCarbonFootprint);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof AnswerCompanyCarbonFootprintScopeRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AnswerCompanyCarbonFootprintScopeRequest;
    }
}
