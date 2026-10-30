<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution;

use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;

abstract class OperatorContributionPublicServiceTestCase extends OperatorContributionServiceTestCase
{
    protected InMemoryUserRepository $userRepository;
    protected OperatorContributionAuthenticator $authenticator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = UserRepositoryMother::inMemory();
        $this->authenticator = new OperatorContributionAuthenticator(
            $this->contributionRepository,
            $this->userRepository,
            $this->companyCarbonFootprintRepository,
        );
    }

    protected function linkedContribution(CompanyCarbonFootprint $footprint, bool $consented = true, string $propertyId = 'franchised-1'): OperatorContribution
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('operator-' . uniqid(), Role::Provider, new AccountId(self::ACCOUNT_ID));
        $this->userRepository->add($user);

        $contribution = OperatorContributionMother::pending(
            companyCarbonFootprintId: (string) $footprint->id(),
            propertyId: $propertyId,
            accountId: self::ACCOUNT_ID,
            userId: (string) $user->id(),
            emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(),
            year: 2025,
        );

        if ($consented) {
            OperatorContributionMother::withConsent($contribution);
        }

        $this->contributionRepository->add($contribution);

        return $contribution;
    }
}
