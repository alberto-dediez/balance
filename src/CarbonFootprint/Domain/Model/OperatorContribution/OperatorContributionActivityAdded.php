<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OperatorContributionActivityAdded extends DomainEvent
{
    protected static string $eventName = 'operatorContributionActivityAdded';

    public function __construct(
        private OperatorContributionId $contributionId,
        private ActivityId $activityId,
        private Scope $scope,
        private Source $source,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionId(): OperatorContributionId
    {
        return $this->contributionId;
    }

    public function activityId(): ActivityId
    {
        return $this->activityId;
    }

    public function scope(): Scope
    {
        return $this->scope;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionId();
    }

    public function payload(): array
    {
        return [
            'contributionId' => (string) $this->contributionId,
            'activityId' => (string) $this->activityId,
            'scope' => $this->scope->value,
            'source' => $this->source->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $scope = Scope::tryFrom($payload['scope']);
        assert(null !== $scope);

        $source = Source::tryFrom($payload['source']);
        assert(null !== $source);

        return new self(
            contributionId: new OperatorContributionId($payload['contributionId']),
            activityId: new ActivityId($payload['activityId']),
            scope: $scope,
            source: $source,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
