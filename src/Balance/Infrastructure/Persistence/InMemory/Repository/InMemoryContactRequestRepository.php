<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\InMemory\Repository;

use App\Balance\Domain\Model\ContactRequest\ContactRequest;
use App\Balance\Domain\Model\ContactRequest\ContactRequestAlreadyExistsException;
use App\Balance\Domain\Model\ContactRequest\ContactRequestFilter;
use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Balance\Domain\Model\ContactRequest\ContactRequestRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryContactRequestRepository implements ContactRequestRepository
{
    /** @param array<string, ContactRequest> $contactRequests */
    public function __construct(
        private array $contactRequests = [],
    ) {}

    public function add(ContactRequest $contactRequest): void
    {
        foreach ($this->contactRequests as $existing) {
            if (
                $existing->origin() === $contactRequest->origin()
                && $existing->originId() === $contactRequest->originId()
                && !$existing->id()->equals($contactRequest->id())
            ) {
                throw new ContactRequestAlreadyExistsException($contactRequest->origin(), $contactRequest->originId());
            }
        }

        $this->contactRequests[(string) $contactRequest->id()] = $contactRequest;
    }

    public function find(ContactRequestId $contactRequestId): ?ContactRequest
    {
        return $this->contactRequests[(string) $contactRequestId] ?? null;
    }

    public function findOneByOrigin(ProjectContributionOrigin $origin, string $originId): ?ContactRequest
    {
        foreach ($this->contactRequests as $contactRequest) {
            if ($origin === $contactRequest->origin() && $originId === $contactRequest->originId()) {
                return $contactRequest;
            }
        }

        return null;
    }

    private function matches(ContactRequest $contactRequest, ContactRequestFilter $filter): bool
    {
        if (null !== $filter->resolved && $filter->resolved === $contactRequest->isPending()) {
            return false;
        }

        if (
            null !== $filter->accountId
            && (null === $contactRequest->accountId() || !$filter->accountId->equals($contactRequest->accountId()))
        ) {
            return false;
        }

        if (
            !in_array($filter->search, [null, ''], true)
            && !str_contains(mb_strtolower($contactRequest->contactName()), mb_strtolower($filter->search))
            && !str_contains(mb_strtolower($contactRequest->contactEmail()), mb_strtolower($filter->search))
        ) {
            return false;
        }

        return true;
    }

    /** @return array<int, ContactRequest> */
    public function findByFilter(ContactRequestFilter $filter): array
    {
        $matching = array_values(array_filter(
            $this->contactRequests,
            fn(ContactRequest $contactRequest): bool => $this->matches($contactRequest, $filter),
        ));

        $way = 'requestedAt' === $filter->orderBy && 'ASC' === $filter->orderWay ? 1 : -1;

        usort(
            $matching,
            static fn(ContactRequest $a, ContactRequest $b): int => $way * ($a->requestedAt() <=> $b->requestedAt())
                ?: strcmp((string) $a->id(), (string) $b->id()),
        );

        if ($filter->limit <= 0) {
            return $matching;
        }

        return array_slice($matching, $filter->page * $filter->limit, $filter->limit);
    }

    public function totalResultsByFilter(ContactRequestFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
