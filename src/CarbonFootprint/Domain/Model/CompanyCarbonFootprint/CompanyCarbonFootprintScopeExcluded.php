<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CompanyCarbonFootprintScopeExcluded extends DomainEvent
{
    protected static string $eventName = 'companyCarbonFootprintScopeExcluded';

    public function __construct(
        private CompanyCarbonFootprintId $companyCarbonFootprintId,
        private Scope $scope,
        private ExclusionReason $reason,
        private ?string $comment = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function companyCarbonFootprintId(): CompanyCarbonFootprintId
    {
        return $this->companyCarbonFootprintId;
    }

    public function scope(): Scope
    {
        return $this->scope;
    }

    public function reason(): ExclusionReason
    {
        return $this->reason;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->companyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'companyCarbonFootprintId' => (string) $this->companyCarbonFootprintId,
            'scope' => $this->scope->value,
            'reason' => $this->reason->value,
            'comment' => $this->comment,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $scope = Scope::tryFrom($payload['scope']);
        assert(null !== $scope);

        $reason = ExclusionReason::tryFrom($payload['reason']);
        assert(null !== $reason);

        return new self(
            companyCarbonFootprintId: new CompanyCarbonFootprintId($payload['companyCarbonFootprintId']),
            scope: $scope,
            reason: $reason,
            comment: $payload['comment'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
