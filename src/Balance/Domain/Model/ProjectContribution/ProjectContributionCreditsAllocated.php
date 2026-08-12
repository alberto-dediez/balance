<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProjectContributionCreditsAllocated extends DomainEvent
{
    protected static string $eventName = 'projectContributionCreditsAllocated';

    /** @param list<AllocatedCreditLine> $lines */
    public function __construct(
        private ProjectContributionId $projectContributionId,
        private array $lines,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function projectContributionId(): ProjectContributionId
    {
        return $this->projectContributionId;
    }

    /** @return list<AllocatedCreditLine> */
    public function lines(): array
    {
        return $this->lines;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->projectContributionId();
    }

    public function payload(): array
    {
        return [
            'projectContributionId' => (string) $this->projectContributionId,
            'lines' => array_map(self::lineToPrimitives(...), $this->lines),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        /** @var list<array<string, mixed>> $lines */
        $lines = $payload['lines'] ?? [];

        return new ProjectContributionCreditsAllocated(
            projectContributionId: new ProjectContributionId($payload['projectContributionId']),
            lines: array_map(self::lineFromPrimitives(...), $lines),
            id: $id,
            occurredOn: $occurredOn,
        );
    }

    /** @return array<string, mixed> */
    private static function lineToPrimitives(AllocatedCreditLine $line): array
    {
        return [
            'position' => $line->position,
            'creditPurchaseId' => (string) $line->creditPurchaseId,
            'purchaseReference' => $line->purchaseReference,
            'tonnes' => $line->tonnes,
            'startTonnes' => $line->startTonnes,
        ];
    }

    /** @param array<string, mixed> $line */
    private static function lineFromPrimitives(array $line): AllocatedCreditLine
    {
        return new AllocatedCreditLine(
            position: (int) $line['position'],
            creditPurchaseId: new CreditPurchaseId((string) $line['creditPurchaseId']),
            purchaseReference: null !== ($line['purchaseReference'] ?? null) ? (string) $line['purchaseReference'] : null,
            tonnes: (float) $line['tonnes'],
            startTonnes: (float) ($line['startTonnes'] ?? 0.0),
        );
    }
}
