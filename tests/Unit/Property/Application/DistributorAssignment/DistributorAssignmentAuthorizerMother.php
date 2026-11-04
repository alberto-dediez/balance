<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\DistributorAssignment;

use App\Property\Application\DistributorAssignment\DistributorAssignmentAuthorizer;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;

class DistributorAssignmentAuthorizerMother
{
    /** @param list<Property> $properties */
    public static function create(array $properties = [], ?AccountId $currentAccountId = null, ?PropertyRepository $propertyRepository = null): DistributorAssignmentAuthorizer
    {
        if (!$propertyRepository instanceof PropertyRepository) {
            $propertyRepository = new InMemoryPropertyRepository();

            foreach ($properties as $property) {
                $propertyRepository->add($property);
            }
        }

        return new DistributorAssignmentAuthorizer(
            $propertyRepository,
            $currentAccountId instanceof AccountId ? new StubCurrentAccountIdProvider($currentAccountId) : null,
        );
    }
}
