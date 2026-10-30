<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Issue;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Generate\GeneratePropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Issue\IssuePropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Issue\IssuePropertyCarbonFootprintCertificateService;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintAlreadyCertifiedException;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintNotCertifiableException;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateStatus;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCarbonFootprintReportRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Send\SpyDeferredCommandHandler;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class IssuePropertyCarbonFootprintCertificateServiceTest extends TestCase
{
    private const string ACCOUNT_ID = 'account-id-test';

    private InMemoryPropertyCarbonFootprintCertificateRepository $certificates;
    private InMemoryPropertyCarbonFootprintRepository $footprints;
    private InMemoryCarbonFootprintReportRepository $reports;
    private InMemoryActivityRepository $activities;
    private EmissionsBreakdownBuilder $breakdownBuilder;
    private SpyDeferredCommandHandler $deferredCommands;
    private IssuePropertyCarbonFootprintCertificateService $sut;
    private PropertyId $propertyId;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->deferredCommands = new SpyDeferredCommandHandler();
        $this->deferredCommands->register();

        $this->certificates = PropertyCarbonFootprintCertificateRepositoryMother::inMemory();
        $this->footprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->reports = CarbonFootprintReportRepositoryMother::inMemory();
        $this->activities = ActivityRepositoryMother::inMemory();
        $this->breakdownBuilder = new EmissionsBreakdownBuilder(
            $this->activities,
            new DerivedSourceEmissionsCalculator($this->activities, CountedActivityReferencesMother::create()),
            CountedActivityReferencesMother::create(),
        );

        $this->propertyId = PropertyId::generate();

        $properties = PropertyRepositoryMother::inMemory();
        $properties->add(PropertyMother::from($this->propertyId, 'Hotel Los Olivos', new AccountId(self::ACCOUNT_ID)));

        $accounts = AccountRepositoryMother::inMemory();
        $accounts->add(AccountMother::fromNameAndId('Hoteles Los Olivos S.L.', self::ACCOUNT_ID));

        $this->sut = new IssuePropertyCarbonFootprintCertificateService(
            $this->certificates,
            $this->footprints,
            $this->reports,
            $properties,
            $accounts,
            new PropertyCarbonFootprintAuthorizer(),
            $this->breakdownBuilder,
            new FakePropertyCarbonFootprintCertificateCodeGenerator(),
            new FakePropertyCarbonFootprintCertificateVerificationTokenGenerator(),
        );
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();
    }

    public function testANotCompletedFootprintIsNotCertifiable(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) $this->propertyId, (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $this->footprints->add($footprint);

        $this->expectException(PropertyCarbonFootprintNotCertifiableException::class);

        $this->issue($footprint, 41000);
    }

    public function testACompletedFootprintWithoutReportIsNotCertifiable(): void
    {
        $footprint = $this->completedFootprint();

        $this->expectException(PropertyCarbonFootprintNotCertifiableException::class);

        $this->issue($footprint, 41000);
    }

    public function testACompletedFootprintWithAReportPendingReviewIsNotCertifiable(): void
    {
        $footprint = $this->completedFootprint();
        $this->reports->add(CarbonFootprintReportMother::pendingReview(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), new AccountId(self::ACCOUNT_ID)));

        $this->expectException(PropertyCarbonFootprintNotCertifiableException::class);

        $this->issue($footprint, 41000);
    }

    public function testACompletedFootprintWithAGeneratedReportIssuesTheCertificate(): void
    {
        $footprint = $this->certifiableFootprint();

        $certificate = $this->issue($footprint, 3);

        $breakdown = $this->breakdownBuilder->build($footprint, Scope::cases());

        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Pending, $certificate->status());
        $this->assertSame((float) $footprint->totalEmissions()->co2eq(), $certificate->totalCo2eqKg());
        $this->assertSame((float) $breakdown->byStage['scope_1']->co2eq(), $certificate->scope1Co2eqKg());
        $this->assertSame((float) $breakdown->byStage['scope_2']->co2eq(), $certificate->scope2Co2eqKg());
        $this->assertSame((float) $breakdown->byStage['scope_3']->co2eq(), $certificate->scope3Co2eqKg());
        $this->assertGreaterThan(0.0, $certificate->scope2Co2eqKg());
        $this->assertGreaterThan(0.0, (float) $certificate->scope3Co2eqKg());
        $this->assertSame(
            $certificate->totalCo2eqKg(),
            $certificate->scope1Co2eqKg() + $certificate->scope2Co2eqKg() + (float) $certificate->scope3Co2eqKg(),
        );
        $this->assertSame(round($certificate->totalCo2eqKg() / 3, 2), $certificate->co2eqKgPerStay());
        $this->assertSame('Hotel Los Olivos', $certificate->propertyName());
        $this->assertSame('Hoteles Los Olivos S.L.', $certificate->accountName());
        $this->assertSame($footprint->year(), $certificate->year());
        $this->assertNotNull($this->certificates->find($certificate->id()));
    }

    public function testAnExcludedScope3IsCertifiedAsNull(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Excluded((string) $this->propertyId, (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $combustible = CombustibleActivityMother::registered(new AccountId(self::ACCOUNT_ID));
        $this->activities->save($combustible);
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $this->complete($footprint);
        $this->reports->add(CarbonFootprintReportMother::generated(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), new AccountId(self::ACCOUNT_ID)));

        $certificate = $this->issue($footprint, 41000);

        $this->assertNull($certificate->scope3Co2eqKg());
    }

    public function testIssuingWithACurrentCertificateIsRejected(): void
    {
        $footprint = $this->certifiableFootprint();

        $first = $this->issue($footprint, 41000);

        try {
            $this->issue($footprint, 42000);
            $this->fail('Issuing with a current certificate was expected to be rejected.');
        } catch (PropertyCarbonFootprintAlreadyCertifiedException) {
        }

        $this->assertFalse($first->isRevoked());
        $this->assertCount(1, $this->certificates->findByPropertyCarbonFootprintId($footprint->id()));

        $current = $this->certificates->findCurrentByPropertyCarbonFootprintId($footprint->id());
        $this->assertNotNull($current);
        $this->assertTrue($first->id()->equals($current->id()));
        $this->assertSame(41000, $current->stays());
    }

    public function testTheGenerationIsQueuedWithLowPriority(): void
    {
        $footprint = $this->certifiableFootprint();

        $certificate = $this->issue($footprint, 41000);

        $commands = $this->deferredCommands->commands();

        $this->assertCount(1, $commands);
        $this->assertSame(Priority::LOW, $commands[0]->priority());

        $command = $commands[0]->command();
        $this->assertInstanceOf(GeneratePropertyCarbonFootprintCertificateRequest::class, $command);
        $this->assertTrue($certificate->id()->equals($command->propertyCarbonFootprintCertificateId));
    }

    public function testZeroStaysAreRejected(): void
    {
        $footprint = $this->certifiableFootprint();

        $this->expectException(\InvalidArgumentException::class);

        $this->issue($footprint, 0);
    }

    public function testAnOwnerOfAnotherAccountIsNotAuthorized(): void
    {
        $footprint = $this->certifiableFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new IssuePropertyCarbonFootprintCertificateRequest($footprint->id(), 41000),
            UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, AccountId::generate()),
        );
    }

    public function testAMissingFootprintThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new IssuePropertyCarbonFootprintCertificateRequest(PropertyCarbonFootprintId::generate(), 41000), $this->ownerUser());
    }

    private function issue(PropertyCarbonFootprint $footprint, int $stays): PropertyCarbonFootprintCertificate
    {
        return ($this->sut)(new IssuePropertyCarbonFootprintCertificateRequest($footprint->id(), $stays), $this->ownerUser())->certificate;
    }

    private function certifiableFootprint(): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) $this->propertyId, (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID);

        $combustible = CombustibleActivityMother::registered(new AccountId(self::ACCOUNT_ID));
        $electricity = ElectricityActivityMother::registered(new AccountId(self::ACCOUNT_ID));
        $purchase = PurchaseServiceActivityMother::registered(new AccountId(self::ACCOUNT_ID));
        $this->activities->save($combustible);
        $this->activities->save($electricity);
        $this->activities->save($purchase);
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity($electricity->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $footprint->addActivity($purchase->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));

        $this->complete($footprint);

        $this->reports->add(CarbonFootprintReportMother::generated(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), new AccountId(self::ACCOUNT_ID)));

        return $footprint;
    }

    private function completedFootprint(): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) $this->propertyId, (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $this->complete($footprint);

        return $footprint;
    }

    private function complete(PropertyCarbonFootprint $footprint): void
    {
        $footprint->complete($this->breakdownBuilder->build($footprint, Scope::cases())->total);
        $this->footprints->add($footprint);
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT_ID));
    }
}
