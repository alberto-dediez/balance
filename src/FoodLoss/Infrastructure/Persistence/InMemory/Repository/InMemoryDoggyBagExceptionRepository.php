<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagException;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionFilter;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\Property\Domain\Model\Property\PropertyId;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: DoggyBagExceptionRepository::class, public: true)]
#[When(env: 'test')]
class InMemoryDoggyBagExceptionRepository implements DoggyBagExceptionRepository
{
    /** @param array<string, DoggyBagException> $doggyBagExceptions */
    public function __construct(
        private array $doggyBagExceptions = [],
        private ?CriticalPointRepository $criticalPointRepository = null,
    ) {}

    public function add(DoggyBagException $doggyBagException): void
    {
        if (isset($this->doggyBagExceptions[(string) $doggyBagException->id()])) {
            return;
        }

        $this->doggyBagExceptions[(string) $doggyBagException->id()] = $doggyBagException;
    }

    public function remove(DoggyBagException $doggyBagException): void
    {
        if (!isset($this->doggyBagExceptions[(string) $doggyBagException->id()])) {
            return;
        }

        unset($this->doggyBagExceptions[(string) $doggyBagException->id()]);
    }

    public function find(DoggyBagExceptionId $doggyBagExceptionId): ?DoggyBagException
    {
        return (isset($this->doggyBagExceptions[(string) $doggyBagExceptionId]) && false == $this->doggyBagExceptions[(string) $doggyBagExceptionId]->isRemoved())
            ? $this->doggyBagExceptions[(string) $doggyBagExceptionId]
            : null
        ;
    }

    /** @return array<int, DoggyBagException> */
    public function findByFilter(DoggyBagExceptionFilter $filter): array
    {
        $results = [];

        if (0 === count($this->doggyBagExceptions)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->doggyBagExceptions as $doggyBagException) {
            if (
                !empty($filter->description)
                && !str_contains(strtolower((string) $doggyBagException->description()), strtolower($filter->description))
            ) {
                continue;
            }

            if (null !== $filter->accountId && !$doggyBagException->accountId()?->equals($filter->accountId)) {
                continue;
            }

            if ($filter->propertyId instanceof PropertyId && null !== $this->criticalPointRepository) {
                $criticalPoint = null !== $doggyBagException->criticalPointId() ? $this->criticalPointRepository->find($doggyBagException->criticalPointId()) : null;
                $criticalPointPropertyId = $criticalPoint?->propertyId();
                if (null === $criticalPointPropertyId || !$filter->propertyId->equals($criticalPointPropertyId)) {
                    continue;
                }
            }

            if (true == $doggyBagException->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $doggyBagException;
        }

        return $results;
    }

    public function totalResultsByFilter(DoggyBagExceptionFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
