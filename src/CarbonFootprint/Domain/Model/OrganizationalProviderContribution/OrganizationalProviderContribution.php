<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Consent\ConsentProof;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class OrganizationalProviderContribution implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<OrganizationalProviderContributionSource>|Collection<int, OrganizationalProviderContributionSource> */
    private iterable $sources = [];

    /** @param array<DomainEvent> $events */
    public function __construct(
        private OrganizationalProviderContributionId $id,
        private CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        private string $carbonFootprintId = '',
        private ?AccountId $accountId = null,
        private ?ProviderId $providerId = null,
        private ?PropertyId $propertyId = null,
        private ProviderContributionMode $mode = ProviderContributionMode::Manual,
        #[\SensitiveParameter]
        private ?string $token = null,
        private ProviderContributionStatus $status = ProviderContributionStatus::Pending,
        private ?\DateTimeImmutable $answeredAt = null,
        private ?\DateTimeImmutable $ignoredAt = null,
        private ?string $companyName = null,
        private ?string $service = null,
        private ?ConsentProof $consentProof = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): OrganizationalProviderContributionId
    {
        return $this->id;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): string
    {
        return $this->carbonFootprintId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function providerId(): ?ProviderId
    {
        return $this->providerId;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function mode(): ProviderContributionMode
    {
        return $this->mode;
    }

    public function token(): string
    {
        return $this->token ?? '';
    }

    public function status(): ProviderContributionStatus
    {
        return $this->status;
    }

    public function answeredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function ignoredAt(): ?\DateTimeImmutable
    {
        return $this->ignoredAt;
    }

    public function companyName(): ?string
    {
        return $this->companyName;
    }

    public function service(): ?string
    {
        return $this->service;
    }

    public function consentProof(): ?ConsentProof
    {
        return $this->consentProof;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    /** @return list<Source> */
    public function sources(): array
    {
        $sources = array_map(
            static fn(OrganizationalProviderContributionSource $source): Source => $source->source(),
            $this->sourceCollection()->toArray(),
        );

        return array_values(array_filter(
            Scope::Scope3->providerSources(),
            static fn(Source $source): bool => in_array($source, $sources, true),
        ));
    }

    public function covers(Source $source): bool
    {
        return in_array($source, $this->sources(), true);
    }

    public function isManual(): bool
    {
        return $this->mode->isManual();
    }

    public function isRequested(): bool
    {
        return $this->mode->isRequested();
    }

    public function hasConsent(): bool
    {
        return null !== $this->consentProof?->consentedAt();
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /** @param list<Source> $sources */
    public function register(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        AccountId $accountId,
        ProviderId $providerId,
        ?PropertyId $propertyId,
        ProviderContributionMode $mode,
        #[\SensitiveParameter]
        string $token,
        array $sources,
        ?string $companyName,
    ): void {
        $sources = self::assertValidSources($sources);

        if ($mode->isRequested() && '' === $token) {
            throw new \InvalidArgumentException('A requested provider contribution needs a token');
        }

        $this->recordApplyAndPublish(
            new OrganizationalProviderContributionRegistered(
                contributionId: $this->id(),
                subjectType: $subjectType,
                carbonFootprintId: $carbonFootprintId,
                accountId: $accountId,
                providerId: $providerId,
                propertyId: $propertyId,
                mode: $mode,
                token: $mode->isRequested() ? $token : '',
                sources: $sources,
                companyName: $companyName,
            ),
        );
    }

    /** @param list<Source> $sources */
    public function changeSources(array $sources): void
    {
        $sources = self::assertValidSources($sources);

        $this->recordApplyAndPublish(
            new OrganizationalProviderContributionSourcesChanged(
                contributionId: $this->id(),
                sources: $sources,
            ),
        );
    }

    public function answer(): void
    {
        if ($this->status->isAnswered()) {
            return;
        }

        $this->recordApplyAndPublish(
            new OrganizationalProviderContributionAnswered(
                contributionId: $this->id(),
            ),
        );
    }

    public function ignore(): void
    {
        if ($this->status->isIgnored()) {
            return;
        }

        $this->recordApplyAndPublish(
            new OrganizationalProviderContributionIgnored(
                contributionId: $this->id(),
            ),
        );
    }

    public function unignore(): void
    {
        if (!$this->status->isIgnored()) {
            return;
        }

        $this->recordApplyAndPublish(
            new OrganizationalProviderContributionUnignored(
                contributionId: $this->id(),
            ),
        );
    }

    public function resendNotification(): void
    {
        if (!$this->isRequested() || $this->status->isIgnored()) {
            throw new \LogicException('Only pending or answered requested provider contributions can be resent');
        }

        $this->recordApplyAndPublish(
            new OrganizationalProviderContributionNotificationResent(
                contributionId: $this->id(),
            ),
        );
    }

    public function updateGeneralInfo(?string $companyName, ?string $service): void
    {
        $this->recordApplyAndPublish(
            new OrganizationalProviderContributionGeneralInfoUpdated(
                contributionId: $this->id(),
                companyName: $companyName,
                service: $service,
            ),
        );
    }

    public function grantConsent(ConsentProof $consentProof): void
    {
        $this->recordApplyAndPublish(
            new OrganizationalProviderContributionConsentGranted(
                contributionId: $this->id(),
                consentProof: $consentProof,
            ),
        );
    }

    public function remove(): void
    {
        if ($this->removed) {
            return;
        }

        $this->recordApplyAndPublish(
            new OrganizationalProviderContributionRemoved(
                contributionId: $this->id(),
            ),
        );
    }

    public function applyOrganizationalProviderContributionRegistered(OrganizationalProviderContributionRegistered $event): void
    {
        $this->subjectType = $event->subjectType();
        $this->carbonFootprintId = $event->carbonFootprintId();
        $this->accountId = $event->accountId();
        $this->providerId = $event->providerId();
        $this->propertyId = $event->propertyId();
        $this->mode = $event->mode();
        $this->token = '' === $event->token() ? null : $event->token();
        $this->companyName = $event->companyName();
        $this->status = $event->mode()->isManual() ? ProviderContributionStatus::Answered : ProviderContributionStatus::Pending;
        $this->answeredAt = $event->mode()->isManual() ? $event->occurredOn() : null;
        $this->registeredAt = $event->occurredOn();
        $this->replaceSources($event->sources());
    }

    public function applyOrganizationalProviderContributionSourcesChanged(OrganizationalProviderContributionSourcesChanged $event): void
    {
        $this->replaceSources($event->sources());
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalProviderContributionAnswered(OrganizationalProviderContributionAnswered $event): void
    {
        $this->status = ProviderContributionStatus::Answered;
        $this->answeredAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalProviderContributionIgnored(OrganizationalProviderContributionIgnored $event): void
    {
        $this->status = ProviderContributionStatus::Ignored;
        $this->ignoredAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalProviderContributionUnignored(OrganizationalProviderContributionUnignored $event): void
    {
        $this->status = $this->answeredAt instanceof \DateTimeImmutable ? ProviderContributionStatus::Answered : ProviderContributionStatus::Pending;
        $this->ignoredAt = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalProviderContributionNotificationResent(OrganizationalProviderContributionNotificationResent $event): void
    {
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalProviderContributionGeneralInfoUpdated(OrganizationalProviderContributionGeneralInfoUpdated $event): void
    {
        $this->companyName = $event->companyName();
        $this->service = $event->service();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalProviderContributionConsentGranted(OrganizationalProviderContributionConsentGranted $event): void
    {
        $this->consentProof = $event->consentProof();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalProviderContributionRemoved(OrganizationalProviderContributionRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    /**
     * @param list<Source> $sources
     *
     * @return list<Source>
     */
    private static function assertValidSources(array $sources): array
    {
        $unique = [];
        foreach ($sources as $source) {
            if (!in_array($source, Scope::Scope3->providerSources(), true)) {
                throw new \InvalidArgumentException(sprintf('Source %s cannot be contributed by a provider', $source->value));
            }

            if (!in_array($source, $unique, true)) {
                $unique[] = $source;
            }
        }

        if ([] === $unique) {
            throw new \InvalidArgumentException('A provider contribution must cover at least one source');
        }

        return $unique;
    }

    /** @param list<Source> $sources */
    private function replaceSources(array $sources): void
    {
        $collection = $this->sourceCollection();

        foreach ($collection->toArray() as $existing) {
            if (!in_array($existing->source(), $sources, true)) {
                $collection->removeElement($existing);
            }
        }

        $current = array_map(
            static fn(OrganizationalProviderContributionSource $source): Source => $source->source(),
            $collection->toArray(),
        );

        foreach ($sources as $source) {
            if (!in_array($source, $current, true)) {
                $collection->add(new OrganizationalProviderContributionSource(
                    id: OrganizationalProviderContributionSourceId::generate(),
                    contribution: $this,
                    source: $source,
                ));
            }
        }
    }

    /** @return Collection<int, OrganizationalProviderContributionSource> */
    private function sourceCollection(): Collection
    {
        if (!$this->sources instanceof Collection) {
            $this->sources = new ArrayCollection(
                $this->sources instanceof \Traversable ? iterator_to_array($this->sources) : $this->sources,
            );
        }

        return $this->sources;
    }
}
