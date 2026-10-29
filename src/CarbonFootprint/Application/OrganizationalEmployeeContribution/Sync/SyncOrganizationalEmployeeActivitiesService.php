<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync;

use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\Mobility\MobilityFactorBasisProvider;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MobilityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\OvernightStayAnswer;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\MarginOfError;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeContributionsAggregation;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeContributionsAggregator;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class SyncOrganizationalEmployeeActivitiesService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OrganizationalCarbonFootprintLocator $locator,
        private OrganizationalEmployeeParticipationRepository $participationRepository,
        private OrganizationalEmployeeContributionRepository $contributionRepository,
        private OrganizationalEmployeeContributionsAggregator $aggregator,
        private MobilityFactorBasisProvider $mobilityFactorBasisProvider,
        private MobilityRepository $mobilityRepository,
        private OvernightStayRepository $overnightStayRepository,
        private CompanyProperties $companyProperties,
        private StageAnswersSynchronizer $answersSynchronizer,
    ) {}

    public function __invoke(SyncOrganizationalEmployeeActivitiesRequest $request, User $user): SyncOrganizationalEmployeeActivitiesResponse
    {
        $located = $this->locator->locate($request->subjectType, $request->carbonFootprintId);

        $located->denyUnlessCanDoThat(SecurityAction::Edit, $user);

        $participation = $this->participationRepository->findByCarbonFootprint($request->subjectType, $request->carbonFootprintId);

        if (null === $participation) {
            return new SyncOrganizationalEmployeeActivitiesResponse();
        }

        $located->ensureIsEditable();

        $carbonFootprint = $located->carbonFootprint;

        $answered = array_values(array_filter(
            $this->contributionRepository->findByCarbonFootprint($request->subjectType, $request->carbonFootprintId),
            static fn(OrganizationalEmployeeContribution $contribution): bool => $contribution->status()->isAnswered(),
        ));

        $answeredCount = count($answered);
        $totalEmployees = max($participation->totalEmployees() ?? $answeredCount, $answeredCount);
        $marginOfError = $answeredCount > 0
            ? MarginOfError::calculate($answeredCount, $totalEmployees, $participation->confidenceLevel() ?? ConfidenceLevel::NinetyFive)
            : null;

        $participation->recordAggregation($answeredCount, $marginOfError);
        $this->participationRepository->add($participation);

        $excludedSources = array_values(array_filter(
            Scope::Scope3->employeeSources(),
            static fn(Source $source): bool => $carbonFootprint->isStageExcluded(Scope::Scope3) || $carbonFootprint->isSourceExcluded(Scope::Scope3, $source),
        ));

        $sections = array_values(array_filter(
            Scope::Scope3->employeeSections(),
            static fn(SectionDefinition $section): bool => !in_array($section->source, $excludedSources, true),
        ));

        if ([] === $sections) {
            return new SyncOrganizationalEmployeeActivitiesResponse($answeredCount, $totalEmployees, $marginOfError, false, $excludedSources);
        }

        $admittedBlocks = $carbonFootprint instanceof CompanyCarbonFootprint
            ? array_map(static fn(Property $property): string => (string) $property->id(), $this->companyProperties->consolidated($carbonFootprint->companyId()))
            : [];

        $aggregation = $this->aggregator->aggregate($answered, $totalEmployees, $this->mobilityFactorBasisProvider->factorBasisByMobilityId(), $admittedBlocks);

        $emissionFactorYears = new EmployeeEmissionFactorYears($carbonFootprint->calculationYear(), $carbonFootprint->emissionFactorGroupId());
        $overnightStay = null;
        $overnightStayResolved = false;

        foreach ($this->blocksToSync($carbonFootprint, $admittedBlocks) as $block) {
            if ($aggregation->hotelNights($block) > 0.0 && !$overnightStayResolved) {
                $overnightStay = $this->defaultOvernightStay();
                $overnightStayResolved = true;
            }

            $this->answersSynchronizer->sync(
                $carbonFootprint,
                Scope::Scope3,
                $this->sectionAnswers($sections, $aggregation, $block, $overnightStay, $emissionFactorYears),
                $user,
                $sections,
                null,
                true,
                '' === $block ? null : new PropertyId($block),
            );
        }

        $this->locator->save($located);

        return new SyncOrganizationalEmployeeActivitiesResponse(
            $answeredCount,
            $totalEmployees,
            $marginOfError,
            $overnightStayResolved && null === $overnightStay,
            $excludedSources,
            $emissionFactorYears->skipped(),
            $emissionFactorYears->fallbackYears(),
        );
    }

    /**
     * @param list<string> $admittedBlocks
     *
     * @return list<string>
     */
    private function blocksToSync(PropertyCarbonFootprint|CompanyCarbonFootprint $carbonFootprint, array $admittedBlocks): array
    {
        $blocks = array_merge([''], $admittedBlocks);

        foreach ($carbonFootprint->activityReferences() as $reference) {
            $propertyId = $reference->placement->propertyId;

            if ($reference->placement->fromEmployees && null !== $propertyId && !in_array((string) $propertyId, $blocks, true)) {
                $blocks[] = (string) $propertyId;
            }
        }

        return $blocks;
    }

    /**
     * @param list<SectionDefinition> $sections
     *
     * @return list<SectionAnswer>
     */
    private function sectionAnswers(
        array $sections,
        OrganizationalEmployeeContributionsAggregation $aggregation,
        string $block,
        ?OvernightStay $overnightStay,
        EmployeeEmissionFactorYears $emissionFactorYears,
    ): array {
        $sectionAnswers = [];

        foreach ($sections as $section) {
            $answers = match ($section->key) {
                'employeeCommuting' => $this->mobilityAnswers($aggregation->commutingKmByMobilityId($block), $emissionFactorYears),
                'businessTravel' => $this->mobilityAnswers($aggregation->businessTravelKmByMobilityId($block), $emissionFactorYears),
                'businessTravelStays' => $this->overnightStayAnswers($aggregation->hotelNights($block), $overnightStay, $emissionFactorYears),
                default => [],
            };

            $sectionAnswers[] = new SectionAnswer($section, $answers);
        }

        return $sectionAnswers;
    }

    /**
     * @param array<string, float> $kmByMobilityId
     *
     * @return list<MobilityAnswer>
     */
    private function mobilityAnswers(array $kmByMobilityId, EmployeeEmissionFactorYears $emissionFactorYears): array
    {
        $answers = [];

        foreach ($kmByMobilityId as $mobilityId => $km) {
            if ($km <= 0.0) {
                continue;
            }

            $id = new MobilityId((string) $mobilityId);
            $mobility = $this->mobilityRepository->find($id);

            if (!$emissionFactorYears->resolveMobility((string) $id, $mobility)) {
                continue;
            }

            $answers[] = new MobilityAnswer(
                mobilityId: $id,
                quantity: new Quantity($km, Unit::Kilometer),
                emissionFactorYear: $emissionFactorYears->yearFor((string) $id),
            );
        }

        return $answers;
    }

    /** @return list<OvernightStayAnswer> */
    private function overnightStayAnswers(float $hotelNights, ?OvernightStay $overnightStay, EmployeeEmissionFactorYears $emissionFactorYears): array
    {
        if ($hotelNights <= 0.0 || null === $overnightStay) {
            return [];
        }

        $key = (string) $overnightStay->id();

        if (!$emissionFactorYears->resolveOvernightStay($key, $overnightStay)) {
            return [];
        }

        return [
            new OvernightStayAnswer(
                overnightStayId: $overnightStay->id(),
                quantity: new Quantity($hotelNights, Unit::Pernoctacion),
                emissionFactorYear: $emissionFactorYears->yearFor($key),
            ),
        ];
    }

    private function defaultOvernightStay(): ?OvernightStay
    {
        $overnightStays = $this->overnightStayRepository->findByFilter(
            new OvernightStayFilter(category: CategoryOvernight::OrganizingTeam, limit: 1),
        );

        return $overnightStays[0] ?? null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof SyncOrganizationalEmployeeActivitiesRequest;
    }
}
