<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers;

use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ReplaceCompanyCarbonFootprintScopeAnswersService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyProperties $companyProperties,
        private CompanyCarbonFootprintAuthorizer $authorizer,
        private StageAnswersProcessor $answersProcessor,
        private StageAnswersSynchronizer $answersSynchronizer,
    ) {}

    public function __invoke(ReplaceCompanyCarbonFootprintScopeAnswersRequest $request, User $user): ReplaceCompanyCarbonFootprintScopeAnswersResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::Edit, $user);

        $companyPropertyIds = [];
        foreach ($this->companyProperties->consolidated($companyCarbonFootprint->companyId()) as $property) {
            $companyPropertyIds[(string) $property->id()] = $property->id();
        }

        $seenBlocks = [];
        foreach ($request->answersByProperty as $block) {
            $propertyId = $block->propertyId;

            if ($propertyId instanceof PropertyId && !isset($companyPropertyIds[(string) $propertyId])) {
                throw new \InvalidArgumentException('Property does not belong to company');
            }

            if (isset($seenBlocks[(string) $propertyId])) {
                throw new \InvalidArgumentException('Duplicated property block');
            }

            $seenBlocks[(string) $propertyId] = true;
        }

        $exclusions = $companyCarbonFootprint->exclusionsFor($request->scope);

        /** @var array<string, Source> $includedSources */
        $includedSources = [];
        $activities = 0;

        foreach ($request->answersByProperty as $block) {
            $validated = $this->answersProcessor->validate($request->scope, $block->sectionAnswers, []);

            foreach ($validated->sectionAnswers as $sectionAnswer) {
                $activities += count($sectionAnswer->answers);

                $source = $sectionAnswer->definition->source;

                if ([] === $sectionAnswer->answers || !isset($exclusions[$source->value]) || isset($includedSources[$source->value])) {
                    continue;
                }

                $companyCarbonFootprint->includeSource($request->scope, $source);
                $includedSources[$source->value] = $source;
            }

            $this->answersSynchronizer->sync($companyCarbonFootprint, $request->scope, $validated->sectionAnswers, $user, propertyId: $block->propertyId);
        }

        $this->companyCarbonFootprintRepository->add($companyCarbonFootprint);

        return new ReplaceCompanyCarbonFootprintScopeAnswersResponse($activities, array_values($includedSources), count($request->answersByProperty));
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof ReplaceCompanyCarbonFootprintScopeAnswersRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReplaceCompanyCarbonFootprintScopeAnswersRequest;
    }
}
