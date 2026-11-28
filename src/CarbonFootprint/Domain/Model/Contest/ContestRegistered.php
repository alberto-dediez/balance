<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ContestRegistered extends DomainEvent
{
    protected static string $eventName = 'contestRegistered';

    /**
     * @param list<ContestHostReference> $hosts
     * @param list<string>               $allowedCountries
     */
    public function __construct(
        private readonly ContestId $contestId,
        private readonly AccountId $accountId,
        private readonly ContestParticipationMode $participationMode,
        private readonly ContestAwardMode $awardMode,
        private readonly ?int $winnersCount,
        private readonly bool $platformDraw,
        private readonly bool $adultsOnly,
        private readonly string $name,
        private readonly string $description,
        private readonly string $prizeDescription,
        private readonly string $terms,
        private readonly string $organizerName,
        private readonly string $organizerTaxId,
        private readonly string $organizerContactEmail,
        private readonly ?string $privacyPolicyUrl,
        private readonly \DateTimeImmutable $startsAt,
        private readonly \DateTimeImmutable $endsAt,
        private readonly array $hosts,
        private readonly array $allowedCountries,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contestId(): ContestId
    {
        return $this->contestId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function participationMode(): ContestParticipationMode
    {
        return $this->participationMode;
    }

    public function awardMode(): ContestAwardMode
    {
        return $this->awardMode;
    }

    public function winnersCount(): ?int
    {
        return $this->winnersCount;
    }

    public function platformDraw(): bool
    {
        return $this->platformDraw;
    }

    public function adultsOnly(): bool
    {
        return $this->adultsOnly;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function prizeDescription(): string
    {
        return $this->prizeDescription;
    }

    public function terms(): string
    {
        return $this->terms;
    }

    public function organizerName(): string
    {
        return $this->organizerName;
    }

    public function organizerTaxId(): string
    {
        return $this->organizerTaxId;
    }

    public function organizerContactEmail(): string
    {
        return $this->organizerContactEmail;
    }

    public function privacyPolicyUrl(): ?string
    {
        return $this->privacyPolicyUrl;
    }

    public function startsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function endsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    /** @return list<ContestHostReference> */
    public function hosts(): array
    {
        return $this->hosts;
    }

    /** @return list<string> */
    public function allowedCountries(): array
    {
        return $this->allowedCountries;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contestId;
    }

    public function payload(): array
    {
        return [
            'contestId' => (string) $this->contestId,
            'accountId' => (string) $this->accountId,
            'participationMode' => $this->participationMode->value,
            'awardMode' => $this->awardMode->value,
            'winnersCount' => $this->winnersCount,
            'platformDraw' => $this->platformDraw,
            'adultsOnly' => $this->adultsOnly,
            'name' => $this->name,
            'description' => $this->description,
            'prizeDescription' => $this->prizeDescription,
            'terms' => $this->terms,
            'organizerName' => $this->organizerName,
            'organizerTaxId' => $this->organizerTaxId,
            'organizerContactEmail' => $this->organizerContactEmail,
            'privacyPolicyUrl' => $this->privacyPolicyUrl,
            'startsAt' => $this->startsAt->format(\DateTimeInterface::ATOM),
            'endsAt' => $this->endsAt->format(\DateTimeInterface::ATOM),
            'hosts' => array_map(static fn(ContestHostReference $host): string => $host->key(), $this->hosts),
            'allowedCountries' => $this->allowedCountries,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        /** @var list<string> $hosts */
        $hosts = $payload['hosts'] ?? [];
        /** @var list<string> $allowedCountries */
        $allowedCountries = $payload['allowedCountries'] ?? [];

        return new self(
            contestId: new ContestId($payload['contestId']),
            accountId: new AccountId($payload['accountId']),
            participationMode: ContestParticipationMode::from($payload['participationMode']),
            awardMode: ContestAwardMode::from($payload['awardMode']),
            winnersCount: $payload['winnersCount'],
            platformDraw: $payload['platformDraw'],
            adultsOnly: $payload['adultsOnly'],
            name: $payload['name'],
            description: $payload['description'],
            prizeDescription: $payload['prizeDescription'],
            terms: $payload['terms'],
            organizerName: $payload['organizerName'],
            organizerTaxId: $payload['organizerTaxId'],
            organizerContactEmail: $payload['organizerContactEmail'],
            privacyPolicyUrl: $payload['privacyPolicyUrl'],
            startsAt: new \DateTimeImmutable((string) $payload['startsAt']),
            endsAt: new \DateTimeImmutable((string) $payload['endsAt']),
            hosts: array_map(static fn(string $key): ContestHostReference => ContestHostReference::fromKey($key), $hosts),
            allowedCountries: $allowedCountries,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
