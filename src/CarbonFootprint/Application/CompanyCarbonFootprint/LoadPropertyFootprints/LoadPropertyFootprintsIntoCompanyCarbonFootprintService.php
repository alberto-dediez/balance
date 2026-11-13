<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\PropertySectionAnswers;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompletedPropertyFootprintFinder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers\ReplaceCompanyCarbonFootprintScopeAnswersRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers\ReplaceCompanyCarbonFootprintScopeAnswersService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\Property;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class LoadPropertyFootprintsIntoCompanyCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompletedPropertyFootprintFinder $completedPropertyFootprintFinder,
        private CompanyProperties $companyProperties,
        private CompanyCarbonFootprintAuthorizer $authorizer,
        private PropertyActivityAnswerBuilder $answerBuilder,
        private ReplaceCompanyCarbonFootprintScopeAnswersService $replaceAnswers,
        private ActivityRepository $activityRepository,
    ) {}

    public function __invoke(LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest $request, User $user): LoadPropertyFootprintsIntoCompanyCarbonFootprintResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::Edit, $user);

        if (Scope::Scope3 === $request->scope) {
            throw new \InvalidArgumentException('Solo se pueden cargar los alcances 1 y 2');
        }

        if ($companyCarbonFootprint->isPrecalculated()) {
            throw new \InvalidArgumentException('No se pueden cargar huellas de propiedad en una huella ya calculada');
        }

        if (null === $companyCarbonFootprint->accountId()) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint has no account');
        }

        $companyId = $companyCarbonFootprint->companyId()
            ?? throw new \InvalidArgumentException('CompanyCarbonFootprint has no company');

        $properties = $this->companyProperties->consolidated($companyId);

        if ([] === $properties) {
            return new LoadPropertyFootprintsIntoCompanyCarbonFootprintResponse(totalProperties: 0);
        }

        /** @var array<string, PropertySectionAnswers> $blocks */
        $blocks = [];
        /** @var array<string, Emissions> $sourceEmissions */
        $sourceEmissions = [];
        /** @var array<string, Property> $copiedProperties */
        $copiedProperties = [];
        $skipped = [];

        foreach ($properties as $property) {
            $footprint = $this->completedPropertyFootprintFinder->find($property, $companyCarbonFootprint);

            if (null === $footprint) {
                $skipped[] = new SkippedPropertyFootprint($property->id(), $property->name(), PropertyFootprintSkipReason::NoCompletedFootprint);

                continue;
            }

            if ($footprint->isPrecalculated()) {
                $skipped[] = new SkippedPropertyFootprint($property->id(), $property->name(), PropertyFootprintSkipReason::Precalculated);

                continue;
            }

            $built = $this->answerBuilder->build($footprint, $request->scope);

            if (0 === $built->count) {
                $skipped[] = new SkippedPropertyFootprint($property->id(), $property->name(), PropertyFootprintSkipReason::NoActivities);

                continue;
            }

            $key = (string) $property->id();
            $blocks[$key] = new PropertySectionAnswers($property->id(), $built->sectionAnswers);
            $sourceEmissions[$key] = $built->emissions;
            $copiedProperties[$key] = $property;
        }

        if ([] === $blocks) {
            return new LoadPropertyFootprintsIntoCompanyCarbonFootprintResponse(totalProperties: count($properties), skipped: $skipped);
        }

        $replaced = ($this->replaceAnswers)(
            new ReplaceCompanyCarbonFootprintScopeAnswersRequest($companyCarbonFootprint->id(), $request->scope, array_values($blocks)),
            $user,
        );

        $reloaded = $this->companyCarbonFootprintRepository->find($companyCarbonFootprint->id()) ?? $companyCarbonFootprint;

        $differences = [];
        foreach ($copiedProperties as $key => $property) {
            $recalculated = $this->copiedEmissions($reloaded, $request->scope, $property);
            $original = $sourceEmissions[$key];

            if (round((float) $original->co2eq(), 2) !== round((float) $recalculated->co2eq(), 2)) {
                $differences[] = new PropertyEmissionsDifference($property->id(), $property->name(), $original->co2eq(), $recalculated->co2eq());
            }
        }

        return new LoadPropertyFootprintsIntoCompanyCarbonFootprintResponse(
            totalProperties: count($properties),
            copiedProperties: count($blocks),
            activities: $replaced->activities,
            skipped: $skipped,
            differences: $differences,
            includedSources: $replaced->includedSources,
        );
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest;
    }

    private function copiedEmissions(CompanyCarbonFootprint $companyCarbonFootprint, Scope $scope, Property $property): Emissions
    {
        $ids = [];
        foreach ($scope->sections() as $section) {
            $ids = [...$ids, ...$companyCarbonFootprint->activityIds(new ActivityPlacement($scope, $section->source, propertyId: $property->id()))];
        }

        $emissions = new Emissions();
        foreach ($this->activityRepository->findByIds($ids) as $activity) {
            $emissions = $emissions->add($activity->emissions());
        }

        return $emissions;
    }
}
