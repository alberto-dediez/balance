<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\AnswerData;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\CombustibleAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\ElectricityAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\RefrigerantGasAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\ReportedEmissionsAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\TransportAnswerHandler;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityMixEmissionsResolver;
use App\CarbonFootprint\Application\Activity\RefrigerantGasEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterReportedEmissionsActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterTransportActivityService;
use App\CarbonFootprint\Application\Activity\ReportedEmissionsEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\TransportEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateReportedEmissionsActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateTransportActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\OperatorContribution\AnswerData\AnswerOperatorContributionDataRequest;
use App\CarbonFootprint\Application\OperatorContribution\AnswerData\AnswerOperatorContributionDataService;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ReportedEmissionsAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionConsentRequiredException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\OperatorContributionPublicServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;

class AnswerOperatorContributionDataServiceTest extends OperatorContributionPublicServiceTestCase
{
    private const int YEAR = 2025;

    private Combustible $combustible;
    private ElectricityMarketer $electricityMarketer;
    private AnswerOperatorContributionDataService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->combustible = CombustibleMother::fromName('Gas natural');
        $this->combustible->setEmissionFactor($this->factor('2', Unit::KWh));
        $combustibleRepository = CombustibleRepositoryMother::inMemory();
        $combustibleRepository->add($this->combustible);

        $this->electricityMarketer = ElectricityMarketerMother::fromName('Marketer');
        $this->electricityMarketer->setEmissionFactor($this->factor('0.25', Unit::KWh));
        $electricityMarketerRepository = ElectricityMarketerRepositoryMother::inMemory();
        $electricityMarketerRepository->add($this->electricityMarketer);

        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();

        $combustibleSelector = new CombustibleEmissionFactorSelector($combustibleRepository);
        $transportSelector = new TransportEmissionFactorSelector(TransportRepositoryMother::inMemory());
        $refrigerantGasSelector = new RefrigerantGasEmissionFactorSelector(RefrigerantGasRepositoryMother::inMemory());
        $electricitySelector = new ElectricityEmissionFactorSelector($electricityMarketerRepository, GdOFactorRepositoryMother::inMemory());
        $mixResolver = new ElectricityMixEmissionsResolver(ElectricityMixRepositoryMother::inMemory(), $calculator);
        $reportedEmissionsSelector = new ReportedEmissionsEmissionFactorSelector();

        $handlers = new ActivityAnswerHandlerRegistry([
            new CombustibleAnswerHandler(
                new RegisterCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
                new UpdateCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
            ),
            new TransportAnswerHandler(
                new RegisterTransportActivityService($this->activityRepository, $authorizer, $transportSelector, $calculator),
                new UpdateTransportActivityService($this->activityRepository, $authorizer, $transportSelector, $calculator),
            ),
            new RefrigerantGasAnswerHandler(
                new RegisterRefrigerantGasActivityService($this->activityRepository, $authorizer, $refrigerantGasSelector, $calculator),
                new UpdateRefrigerantGasActivityService($this->activityRepository, $authorizer, $refrigerantGasSelector, $calculator),
            ),
            new ElectricityAnswerHandler(
                new RegisterElectricityActivityService($this->activityRepository, $authorizer, $electricitySelector, $calculator, $mixResolver),
                new UpdateElectricityActivityService($this->activityRepository, $authorizer, $electricitySelector, $calculator, $mixResolver),
            ),
            new ReportedEmissionsAnswerHandler(
                new RegisterReportedEmissionsActivityService($this->activityRepository, $authorizer, $reportedEmissionsSelector, $calculator),
                new UpdateReportedEmissionsActivityService($this->activityRepository, $authorizer, $reportedEmissionsSelector, $calculator),
            ),
        ]);

        $this->sut = new AnswerOperatorContributionDataService(
            $this->authenticator,
            $this->contributionRepository,
            new StageAnswersProcessor(),
            new StageAnswersSynchronizer($handlers, new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), $this->propertyRepository)),
            $this->activitiesRemover,
        );
    }

    public function testRegistersTheConsumptionsOfScope1And2AndMarksItAsAnswered(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint());

        ($this->sut)(new AnswerOperatorContributionDataRequest($contribution->token(), [$this->combustibleSection(500.0)], [$this->electricitySection(12000.0)]));

        $this->assertSame(ProviderContributionStatus::Answered, $contribution->status());

        $scope1Ids = $contribution->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $scope2Ids = $contribution->activityIds(new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $this->assertCount(1, $scope1Ids);
        $this->assertCount(1, $scope2Ids);
        $this->assertSame(1000.0, (float) $this->activityRepository->findById($scope1Ids[0])?->emissions()->co2eq());
        $this->assertSame(3000.0, (float) $this->activityRepository->findById($scope2Ids[0])?->emissions()->co2eq());
        $this->assertSame(4000.0, (float) $this->emissionsCalculator->scope1And2($contribution)->co2eq());
    }

    public function testAPrecalculatedContributionRegistersReportedEmissions(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint());
        $contribution->markAsPrecalculated();

        ($this->sut)(new AnswerOperatorContributionDataRequest(
            $contribution->token(),
            [$this->reportedSection(Scope::Scope1, Source::FixedFacilities, 'Caldera', new Quantity(2.0, Unit::Tonne))],
            [],
        ));

        $activityIds = $contribution->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $this->assertCount(1, $activityIds);

        $activity = $this->activityRepository->findById($activityIds[0]);
        $this->assertInstanceOf(ReportedEmissionsActivity::class, $activity);
        $this->assertSame(2000.0, (float) $activity->emissions()->co2eq());
        $this->assertSame(ProviderContributionStatus::Answered, $contribution->status());
    }

    public function testConsumptionSectionsAreRejectedWhenPrecalculated(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint());
        $contribution->markAsPrecalculated();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerOperatorContributionDataRequest($contribution->token(), [$this->combustibleSection(500.0)], []));
    }

    public function testReportedSectionsAreRejectedWhenNotPrecalculated(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint());

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerOperatorContributionDataRequest(
            $contribution->token(),
            [$this->reportedSection(Scope::Scope1, Source::FixedFacilities, 'Caldera', new Quantity(2.0, Unit::Tonne))],
            [],
        ));
    }

    public function testScope3SectionsAreRejected(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint());

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerOperatorContributionDataRequest(
            $contribution->token(),
            [new SectionAnswer(Scope::Scope3->sections()[0], [])],
            [],
        ));
    }

    public function testWithoutConsentIsRejected(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint(), consented: false);

        $this->expectException(OperatorContributionConsentRequiredException::class);

        ($this->sut)(new AnswerOperatorContributionDataRequest($contribution->token(), [$this->combustibleSection(500.0)], []));
    }

    public function testACompletedFootprintIsClosed(): void
    {
        $contribution = $this->linkedContribution($this->completedCompanyFootprint());

        $this->expectException(OperatorContributionClosedException::class);

        ($this->sut)(new AnswerOperatorContributionDataRequest($contribution->token(), [$this->combustibleSection(500.0)], []));
    }

    public function testAnIgnoredContributionIsRejected(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint());
        $contribution->ignore();

        $this->expectException(OperatorContributionIgnoredException::class);

        ($this->sut)(new AnswerOperatorContributionDataRequest($contribution->token(), [$this->combustibleSection(500.0)], []));
    }

    public function testASecondSubmissionReplacesTheActivities(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint());
        ($this->sut)(new AnswerOperatorContributionDataRequest($contribution->token(), [$this->combustibleSection(500.0)], [$this->electricitySection(12000.0)]));
        $electricityIds = $contribution->activityIds(new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $this->assertCount(1, $electricityIds);

        ($this->sut)(new AnswerOperatorContributionDataRequest($contribution->token(), [$this->combustibleSection(250.0)], []));

        $this->assertNull($this->activityRepository->findById($electricityIds[0]));
        $scope1Ids = $contribution->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $this->assertCount(1, $scope1Ids);
        $this->assertNotNull($this->activityRepository->findById($scope1Ids[0]));

        $this->assertCount(1, $contribution->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertCount(0, $contribution->activityIds(new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity)));
        $this->assertSame(500.0, (float) $this->emissionsCalculator->scope1And2($contribution)->co2eq());
    }

    private function combustibleSection(float $quantity): SectionAnswer
    {
        return new SectionAnswer(
            Scope::Scope1->sections()[0],
            [new CombustibleAnswer($this->combustible->id(), new Quantity($quantity, Unit::KWh))],
        );
    }

    private function electricitySection(float $quantity): SectionAnswer
    {
        return new SectionAnswer(
            Scope::Scope2->sections()[0],
            [new ElectricityAnswer($this->electricityMarketer->id(), null, new Quantity($quantity, Unit::KWh))],
        );
    }

    private function reportedSection(Scope $scope, Source $source, string $description, Quantity $quantity): SectionAnswer
    {
        return new SectionAnswer(
            $scope->reportedSectionFor($source),
            [new ReportedEmissionsAnswer($description, $quantity)],
        );
    }

    private function factor(string $co2eq, Unit $unit): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: $co2eq,
            co2: $co2eq,
            ch4: '0',
            n2o: '0',
            year: self::YEAR,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            unit: $unit,
        );
    }
}
