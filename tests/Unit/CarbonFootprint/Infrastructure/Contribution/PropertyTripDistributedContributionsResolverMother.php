<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Contribution;

use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Infrastructure\Contribution\PropertyTripDistributedContributionsResolver;
use App\Property\Application\DistributorAssignment\DistributedPropertiesPolicy;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\Property\Property;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;

class PropertyTripDistributedContributionsResolverMother
{
    /**
     * @param list<Account>               $accounts
     * @param list<Property>              $properties
     * @param list<DistributorAssignment> $assignments
     * @param list<TripCalculationLink>   $links
     * @param list<TripCalculation>       $tripCalculations
     */
    public static function create(
        array $accounts = [],
        array $properties = [],
        array $assignments = [],
        array $links = [],
        array $tripCalculations = [],
    ): PropertyTripDistributedContributionsResolver {
        $indexedAccounts = [];

        foreach ($accounts as $account) {
            $indexedAccounts[(string) $account->id()] = $account;
        }

        $accountRepository = new InMemoryAccountRepository($indexedAccounts);

        $propertyRepository = new InMemoryPropertyRepository();

        foreach ($properties as $property) {
            $propertyRepository->add($property);
        }

        $linkRepository = TripCalculationLinkRepositoryMother::inMemory();

        foreach ($links as $link) {
            $linkRepository->add($link);
        }

        $tripCalculationRepository = TripCalculationRepositoryMother::inMemory();

        foreach ($tripCalculations as $tripCalculation) {
            $tripCalculationRepository->add($tripCalculation);
        }

        return new PropertyTripDistributedContributionsResolver(
            new DistributedPropertiesPolicy(
                DistributorAssignmentRepositoryMother::inMemory($assignments),
                $propertyRepository,
                new DistributorPolicy($accountRepository),
                new AccountFeaturesPolicy($accountRepository),
            ),
            $propertyRepository,
            $linkRepository,
            $tripCalculationRepository,
        );
    }
}
