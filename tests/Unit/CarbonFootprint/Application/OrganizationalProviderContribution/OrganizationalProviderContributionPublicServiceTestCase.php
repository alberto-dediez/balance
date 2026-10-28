<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Shared\Domain\Model\Consent\ConsentProofMother;

abstract class OrganizationalProviderContributionPublicServiceTestCase extends OrganizationalProviderContributionServiceTestCase
{
    protected InMemoryUserRepository $userRepository;
    protected OrganizationalProviderContributionAuthenticator $authenticator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = UserRepositoryMother::inMemory();
        $this->authenticator = new OrganizationalProviderContributionAuthenticator(
            $this->contributionRepository,
            $this->providerRepository,
            $this->userRepository,
            $this->locator,
        );
    }

    protected function linkedProvider(): Provider
    {
        $provider = $this->provider(linked: false);
        $providerUser = $this->providerUser();
        $provider->linkUser($providerUser->id());

        return $provider;
    }

    protected function providerUser(): User
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('provider-' . uniqid() . '@contribution.internal', Role::Provider, new AccountId(self::ACCOUNT_ID));
        $this->userRepository->add($user);

        return $user;
    }

    /** @param list<Source> $sources */
    protected function requestedContribution(
        string $carbonFootprintId,
        Provider $provider,
        array $sources = [Source::UpstreamTransport],
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        ?string $propertyId = null,
        bool $consented = false,
    ): OrganizationalProviderContribution {
        $contribution = OrganizationalProviderContributionMother::requested(
            carbonFootprintId: $carbonFootprintId,
            providerId: (string) $provider->id(),
            sources: $sources,
            subjectType: $subjectType,
            propertyId: $propertyId,
        );

        if ($consented) {
            $contribution->grantConsent(ConsentProofMother::granted());
        }

        $this->contributionRepository->add($contribution);

        return $contribution;
    }

    protected function completedEmissions(): Emissions
    {
        return new Emissions('1', '1', '0', '0');
    }
}
