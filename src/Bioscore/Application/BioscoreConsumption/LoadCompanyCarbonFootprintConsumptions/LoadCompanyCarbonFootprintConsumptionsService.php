<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreConsumption\LoadCompanyCarbonFootprintConsumptions;

use App\Bioscore\Application\BioscoreConsumption\BioscoreConsumptionAnswersBuilder;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionGateway;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionsQuery;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscorePropertyConsumptions;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\PropertySectionAnswers;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers\ReplaceCompanyCarbonFootprintScopeAnswersRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers\ReplaceCompanyCarbonFootprintScopeAnswersService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class LoadCompanyCarbonFootprintConsumptionsService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyProperties $companyProperties,
        private CompanyCarbonFootprintAuthorizer $authorizer,
        private BioscoreConsumptionGateway $gateway,
        private BioscoreConsumptionAnswersBuilder $answersBuilder,
        private ReplaceCompanyCarbonFootprintScopeAnswersService $replaceAnswers,
    ) {}

    public function __invoke(LoadCompanyCarbonFootprintConsumptionsRequest $request, User $user): LoadCompanyCarbonFootprintConsumptionsResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::Edit, $user);

        $accountId = $companyCarbonFootprint->accountId()
            ?? throw new \InvalidArgumentException('CompanyCarbonFootprint has no account');
        $companyId = $companyCarbonFootprint->companyId()
            ?? throw new \InvalidArgumentException('CompanyCarbonFootprint has no company');

        /** @var array<string, PropertyId> $companyPropertyIds */
        $companyPropertyIds = [];
        foreach ($this->companyProperties->consolidated($companyId) as $property) {
            $companyPropertyIds[(string) $property->id()] = $property->id();
        }

        $totalProperties = count($companyPropertyIds);

        if (0 === $totalProperties) {
            return new LoadCompanyCarbonFootprintConsumptionsResponse(totalProperties: 0);
        }

        /** @var list<BioscorePropertyConsumptions> $results */
        $results = [];
        foreach (array_chunk(array_values($companyPropertyIds), BioscoreConsumptionsQuery::MAX_PROPERTIES) as $chunk) {
            $results = [...$results, ...$this->gateway->fetch(new BioscoreConsumptionsQuery($accountId, $chunk, $companyCarbonFootprint->year()))];
        }

        $blocks = [];
        $skipped = [];
        foreach ($results as $entry) {
            if (!isset($companyPropertyIds[(string) $entry->propertyId])) {
                continue;
            }

            $answers = $this->answersBuilder->build($request->scope, $entry);

            $blocks[] = new PropertySectionAnswers($entry->propertyId, $answers->sectionAnswers);
            $skipped = [...$skipped, ...$answers->skipped];
        }

        if ([] === $blocks) {
            return new LoadCompanyCarbonFootprintConsumptionsResponse(totalProperties: $totalProperties);
        }

        $replaced = ($this->replaceAnswers)(
            new ReplaceCompanyCarbonFootprintScopeAnswersRequest($companyCarbonFootprint->id(), $request->scope, $blocks),
            $user,
        );

        return new LoadCompanyCarbonFootprintConsumptionsResponse(
            totalProperties: $totalProperties,
            resolvedProperties: count($blocks),
            activities: $replaced->activities,
            skipped: $skipped,
            includedSources: $replaced->includedSources,
        );
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof LoadCompanyCarbonFootprintConsumptionsRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof LoadCompanyCarbonFootprintConsumptionsRequest;
    }
}
