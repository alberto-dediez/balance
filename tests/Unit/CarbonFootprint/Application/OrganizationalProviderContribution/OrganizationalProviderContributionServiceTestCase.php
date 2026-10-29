<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\ReportedEmissionsAnswerHandler;
use App\CarbonFootprint\Application\Activity\Register\RegisterReportedEmissionsActivityService;
use App\CarbonFootprint\Application\Activity\ReportedEmissionsEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateReportedEmissionsActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalProviderContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

abstract class OrganizationalProviderContributionServiceTestCase extends TestCase
{
    protected const string ACCOUNT_ID = 'account-id';

    protected InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprintRepository;
    protected InMemoryCompanyCarbonFootprintRepository $companyCarbonFootprintRepository;
    protected InMemoryOrganizationalProviderContributionRepository $contributionRepository;
    protected InMemoryProviderRepository $providerRepository;
    protected InMemoryPropertyRepository $propertyRepository;
    protected InMemoryActivityRepository $activityRepository;
    protected OrganizationalCarbonFootprintLocator $locator;
    protected OrganizationalProviderContributionAuthorizer $authorizer;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->propertyCarbonFootprintRepository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->companyCarbonFootprintRepository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->contributionRepository = OrganizationalProviderContributionRepositoryMother::inMemory();
        $this->providerRepository = ProviderRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();
        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->locator = OrganizationalCarbonFootprintLocatorMother::create($this->propertyCarbonFootprintRepository, $this->companyCarbonFootprintRepository);
        $this->authorizer = new OrganizationalProviderContributionAuthorizer();
    }

    protected function propertyFootprint(): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $this->propertyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }

    protected function completedPropertyFootprint(): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $footprint->complete(new Emissions('1', '1', '0', '0'));
        $this->propertyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }

    protected function companyFootprint(?CompanyId $companyId = null): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered((string) ($companyId ?? CompanyId::generate()), (string) CompanyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $this->companyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }

    protected function provider(bool $linked = true, string $accountId = self::ACCOUNT_ID, string $name = 'Logística Sur'): Provider
    {
        $provider = ProviderMother::fromNameAndAccountAndId($name, $accountId, (string) ProviderId::generate());

        if ($linked) {
            $provider->linkUser(UserId::generate());
        }

        $this->providerRepository->add($provider);

        return $provider;
    }

    protected function addProviderActivity(CarbonFootprint $footprint, ProviderId $providerId, Source $source, float $kilograms, ?PropertyId $propertyId = null): ActivityId
    {
        $activity = ReportedEmissionsActivityMother::registered(new AccountId(self::ACCOUNT_ID), kilograms: $kilograms);
        $this->activityRepository->save($activity);

        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope3, $source, $providerId, false, $propertyId));

        return $activity->id();
    }

    protected function addOwnerActivity(CarbonFootprint $footprint, Source $source, float $kilograms): ActivityId
    {
        $activity = ReportedEmissionsActivityMother::registered(new AccountId(self::ACCOUNT_ID), kilograms: $kilograms);
        $this->activityRepository->save($activity);

        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope3, $source));

        return $activity->id();
    }

    protected function synchronizer(): StageAnswersSynchronizer
    {
        $selector = new ReportedEmissionsEmissionFactorSelector();

        return new StageAnswersSynchronizer(
            new ActivityAnswerHandlerRegistry([
                new ReportedEmissionsAnswerHandler(
                    new RegisterReportedEmissionsActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
                    new UpdateReportedEmissionsActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
                ),
            ]),
            new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), $this->propertyRepository),
        );
    }

    protected function owner(string $accountId = self::ACCOUNT_ID): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
    }

    protected function plainUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId(self::ACCOUNT_ID));
    }
}
