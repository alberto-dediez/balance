<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class CarbonFootprintReportRegistered extends DomainEvent
{
    protected static string $eventName = 'carbonFootprintReportRegistered';

    public function __construct(
        private readonly CarbonFootprintReportId $carbonFootprintReportId,
        private readonly CarbonFootprintReportSubjectType $subjectType,
        private readonly string $carbonFootprintId,
        private readonly AccountId $accountId,
        private readonly UserId $requestedBy,
        private readonly Locale $baseLocale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function carbonFootprintReportId(): CarbonFootprintReportId
    {
        return $this->carbonFootprintReportId;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): string
    {
        return $this->carbonFootprintId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function requestedBy(): UserId
    {
        return $this->requestedBy;
    }

    public function baseLocale(): Locale
    {
        return $this->baseLocale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->carbonFootprintReportId;
    }

    public function payload(): array
    {
        return [
            'carbonFootprintReportId' => (string) $this->carbonFootprintReportId,
            'subjectType' => $this->subjectType->value,
            'carbonFootprintId' => $this->carbonFootprintId,
            'accountId' => (string) $this->accountId,
            'requestedBy' => (string) $this->requestedBy,
            'baseLocale' => $this->baseLocale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $subjectType = CarbonFootprintReportSubjectType::tryFrom($payload['subjectType']);
        $baseLocale = Locale::tryFrom($payload['baseLocale']);
        assert(null !== $subjectType && null !== $baseLocale);

        return new self(
            carbonFootprintReportId: new CarbonFootprintReportId($payload['carbonFootprintReportId']),
            subjectType: $subjectType,
            carbonFootprintId: $payload['carbonFootprintId'],
            accountId: new AccountId($payload['accountId']),
            requestedBy: new UserId($payload['requestedBy']),
            baseLocale: $baseLocale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
