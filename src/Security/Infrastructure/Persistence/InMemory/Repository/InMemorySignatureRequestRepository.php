<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestFilter;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemorySignatureRequestRepository implements SignatureRequestRepository
{
    /** @param array<string, SignatureRequest> $signatureRequests */
    public function __construct(
        private array $signatureRequests = [],
    ) {}

    public function add(SignatureRequest $signatureRequest): void
    {
        if (isset($this->signatureRequests[(string) $signatureRequest->id()])) {
            return;
        }

        $this->signatureRequests[(string) $signatureRequest->id()] = $signatureRequest;
    }

    public function remove(SignatureRequest $signatureRequest): void
    {
        if (!isset($this->signatureRequests[(string) $signatureRequest->id()])) {
            return;
        }

        unset($this->signatureRequests[(string) $signatureRequest->id()]);
    }

    public function find(SignatureRequestId $signatureRequestId): ?SignatureRequest
    {
        return (isset($this->signatureRequests[(string) $signatureRequestId]) && false == $this->signatureRequests[(string) $signatureRequestId]->isRemoved())
            ? $this->signatureRequests[(string) $signatureRequestId]
            : null
        ;
    }

    public function findByToken(string $tokenHash): ?SignatureRequest
    {
        foreach ($this->signatureRequests as $signatureRequest) {
            if ($signatureRequest->token()->hash() === $tokenHash) {
                return $signatureRequest;
            }
        }

        return null;
    }

    /** @return array<int, SignatureRequest> */
    public function findByFilter(SignatureRequestFilter $filter): array
    {
        $results = [];

        if (0 === count($this->signatureRequests)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->signatureRequests as $signatureRequest) {
            if (null !== $filter->accountId && (null === $signatureRequest->accountId() || !$filter->accountId->equals($signatureRequest->accountId()))) {
                continue;
            }

            if (null !== $filter->signatureStatus && $filter->signatureStatus !== $signatureRequest->status()) {
                continue;
            }

            if (true == $signatureRequest->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $signatureRequest;
        }

        return $results;
    }

    public function totalResultsByFilter(SignatureRequestFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }

    /** @return array<SignatureRequest> */
    public function findExpired(\DateTimeImmutable $now): array
    {
        $activeStatuses = [SignatureRequestStatus::Pending, SignatureRequestStatus::Viewed];

        return array_values(array_filter(
            $this->signatureRequests,
            static fn(SignatureRequest $sr): bool => !$sr->isRemoved()
                && in_array($sr->status(), $activeStatuses, true)
                && $sr->expiresAt() < $now,
        ));
    }
}
