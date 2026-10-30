<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityReference;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\Locale;
use Doctrine\Common\Collections\Collection;

class OperatorContribution implements CarbonFootprint, \Stringable
{
    use AggregateRoot;

    private const int MAX_CONTACT_NAME_LENGTH = 255;

    /** @var iterable<OperatorContributionActivity>|Collection<int, OperatorContributionActivity> */
    private iterable $activities = [];

    /** @param array<DomainEvent> $events */
    public function __construct(
        private OperatorContributionId $id,
        private ?CompanyCarbonFootprintId $companyCarbonFootprintId = null,
        private ?AccountId $accountId = null,
        private ?PropertyId $propertyId = null,
        private Source $source = Source::Franchises,
        private int $year = 0,
        private ?EmissionFactorGroupId $emissionFactorGroupId = null,
        private string $contactEmail = '',
        private ?string $contactName = null,
        private Locale $contactLocale = Locale::Spanish,
        private ?UserId $userId = null,
        #[\SensitiveParameter]
        private string $token = '',
        private ProviderContributionStatus $status = ProviderContributionStatus::Pending,
        private bool $precalculated = false,
        private ?\DateTimeImmutable $answeredAt = null,
        private ?\DateTimeImmutable $ignoredAt = null,
        private ?ConsentProof $consentProof = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): OperatorContributionId
    {
        return $this->id;
    }

    public function companyCarbonFootprintId(): ?CompanyCarbonFootprintId
    {
        return $this->companyCarbonFootprintId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function emissionFactorGroupId(): ?EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function calculationYear(): int
    {
        return $this->year;
    }

    public function contactEmail(): string
    {
        return $this->contactEmail;
    }

    public function contactName(): ?string
    {
        return $this->contactName;
    }

    public function contactLocale(): Locale
    {
        return $this->contactLocale;
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function status(): ProviderContributionStatus
    {
        return $this->status;
    }

    public function isPrecalculated(): bool
    {
        return $this->precalculated;
    }

    public function answeredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function ignoredAt(): ?\DateTimeImmutable
    {
        return $this->ignoredAt;
    }

    public function consentProof(): ?ConsentProof
    {
        return $this->consentProof;
    }

    public function hasConsent(): bool
    {
        return null !== $this->consentProof?->consentedAt();
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function isCompleted(): bool
    {
        return false;
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

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /** @return list<SectionDefinition> */
    public function sectionsFor(Scope $scope): array
    {
        if (!in_array($scope, [Scope::Scope1, Scope::Scope2], true)) {
            throw new \InvalidArgumentException(sprintf('An operator contribution does not accept the stage "%s"', $scope->value));
        }

        return $scope->sectionsFor($this->precalculated);
    }

    public function register(
        CompanyCarbonFootprintId $companyCarbonFootprintId,
        AccountId $accountId,
        PropertyId $propertyId,
        Source $source,
        int $year,
        ?EmissionFactorGroupId $emissionFactorGroupId,
        string $contactEmail,
        ?string $contactName,
        Locale $contactLocale,
        #[\SensitiveParameter]
        string $token,
    ): void {
        if (!in_array($source, Scope::Scope3->companyOnlySources(), true)) {
            throw new \InvalidArgumentException(sprintf('Source %s cannot be contributed by an operator', $source->value));
        }

        $contactEmail = trim($contactEmail);

        if ('' === $contactEmail || false === filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf('The operator contact email "%s" is not valid', $contactEmail));
        }

        if ('' === $token) {
            throw new \InvalidArgumentException('An operator contribution needs a token');
        }

        $this->recordApplyAndPublish(
            new OperatorContributionRegistered(
                contributionId: $this->id,
                companyCarbonFootprintId: $companyCarbonFootprintId,
                accountId: $accountId,
                propertyId: $propertyId,
                source: $source,
                year: $year,
                emissionFactorGroupId: $emissionFactorGroupId,
                contactEmail: $contactEmail,
                contactName: self::assertValidContactName($contactName),
                contactLocale: $contactLocale,
                token: $token,
            ),
        );
    }

    public function linkUser(UserId $userId): void
    {
        $this->recordApplyAndPublish(
            new OperatorContributionUserLinked(
                contributionId: $this->id,
                userId: $userId,
            ),
        );
    }

    public function grantConsent(ConsentProof $consentProof): void
    {
        $this->recordApplyAndPublish(
            new OperatorContributionConsentGranted(
                contributionId: $this->id,
                consentProof: $consentProof,
            ),
        );
    }

    public function updateContactName(?string $contactName): void
    {
        $contactName = self::assertValidContactName($contactName);

        if ($contactName === $this->contactName) {
            return;
        }

        $this->recordApplyAndPublish(
            new OperatorContributionContactUpdated(
                contributionId: $this->id,
                contactName: $contactName,
            ),
        );
    }

    public function markAsPrecalculated(): void
    {
        if ($this->precalculated) {
            return;
        }

        $this->recordApplyAndPublish(
            new OperatorContributionMarkedAsPrecalculated(
                contributionId: $this->id,
            ),
        );

        $this->removeAllActivities();
    }

    public function unmarkAsPrecalculated(): void
    {
        if (!$this->precalculated) {
            return;
        }

        $this->recordApplyAndPublish(
            new OperatorContributionUnmarkedAsPrecalculated(
                contributionId: $this->id,
            ),
        );

        $this->removeAllActivities();
    }

    public function answer(): void
    {
        if ($this->status->isAnswered()) {
            return;
        }

        $this->recordApplyAndPublish(
            new OperatorContributionAnswered(
                contributionId: $this->id,
            ),
        );
    }

    public function ignore(): void
    {
        if ($this->status->isIgnored()) {
            return;
        }

        $this->recordApplyAndPublish(
            new OperatorContributionIgnored(
                contributionId: $this->id,
            ),
        );
    }

    public function unignore(): void
    {
        if (!$this->status->isIgnored()) {
            return;
        }

        $this->recordApplyAndPublish(
            new OperatorContributionUnignored(
                contributionId: $this->id,
            ),
        );
    }

    public function resendNotification(): void
    {
        if ($this->status->isIgnored()) {
            throw new \LogicException('Only pending or answered operator contributions can be resent');
        }

        $this->recordApplyAndPublish(
            new OperatorContributionNotificationResent(
                contributionId: $this->id,
            ),
        );
    }

    public function remove(): void
    {
        if ($this->removed) {
            return;
        }

        $this->recordApplyAndPublish(
            new OperatorContributionRemoved(
                contributionId: $this->id,
            ),
        );
    }

    /** @return ActivityId[] */
    public function activityIds(ActivityPlacement $placement): array
    {
        $activityIds = [];

        foreach ($this->activities as $activity) {
            if ($activity->placement()->equals($placement)) {
                $activityIds[] = $activity->activityId();
            }
        }

        return $activityIds;
    }

    public function addActivity(ActivityId $activityId, ActivityPlacement $placement): void
    {
        $scope = $placement->stage;

        if (!$scope instanceof Scope || !in_array($scope, [Scope::Scope1, Scope::Scope2], true)) {
            throw new \InvalidArgumentException(sprintf('An operator contribution does not accept activities of the stage "%s"', $placement->stage->key()));
        }

        $sources = array_map(static fn(SectionDefinition $section): Source => $section->source, $this->sectionsFor($scope));

        if (!in_array($placement->source, $sources, true)) {
            throw new \InvalidArgumentException(sprintf('An operator contribution does not accept activities of the source "%s"', $placement->source->value));
        }

        if (null !== $placement->providerId || null !== $placement->propertyId || $placement->fromEmployees) {
            throw new \InvalidArgumentException('An operator contribution only accepts activities without provider, property or employees');
        }

        foreach ($this->activities as $activity) {
            if ($activity->activityId()->equals($activityId)) {
                return;
            }
        }

        $this->recordApplyAndPublish(
            new OperatorContributionActivityAdded(
                contributionId: $this->id,
                activityId: $activityId,
                scope: $scope,
                source: $placement->source,
            ),
        );
    }

    /** @param ActivityId[] $keptIds */
    public function retainActivities(ActivityPlacement $placement, array $keptIds): void
    {
        foreach ($this->activityIds($placement) as $existingId) {
            $kept = false;
            foreach ($keptIds as $keptId) {
                if ($keptId->equals($existingId)) {
                    $kept = true;

                    break;
                }
            }

            if (!$kept) {
                $this->removeActivity($existingId);
            }
        }
    }

    public function removeAllActivities(): void
    {
        foreach ($this->activityReferences() as $reference) {
            $this->removeActivity($reference->activityId);
        }
    }

    /** @return list<ActivityReference> */
    public function activityReferences(): array
    {
        $references = [];

        foreach ($this->activities as $activity) {
            $references[] = new ActivityReference($activity->activityId(), $activity->placement());
        }

        return $references;
    }

    public function excludeSource(CalculationStage $stage, Source $source, ExclusionReason $reason, ?string $comment = null): void
    {
        throw new \LogicException('An operator contribution does not admit exclusions');
    }

    public function includeSource(CalculationStage $stage, Source $source): void
    {
        throw new \LogicException('An operator contribution does not admit exclusions');
    }

    public function excludeStage(CalculationStage $stage, ExclusionReason $reason, ?string $comment = null): void
    {
        throw new \LogicException('An operator contribution does not admit exclusions');
    }

    public function includeStage(CalculationStage $stage): void
    {
        throw new \LogicException('An operator contribution does not admit exclusions');
    }

    public function isStageExcluded(CalculationStage $stage): bool
    {
        return false;
    }

    public function stageExclusionReason(CalculationStage $stage): ?ExclusionReason
    {
        return null;
    }

    public function stageExclusionComment(CalculationStage $stage): ?string
    {
        return null;
    }

    public function exclusionsFor(CalculationStage $stage): array
    {
        return [];
    }

    public function allExclusions(): array
    {
        return [];
    }

    public function applyOperatorContributionRegistered(OperatorContributionRegistered $event): void
    {
        $this->companyCarbonFootprintId = $event->companyCarbonFootprintId();
        $this->accountId = $event->accountId();
        $this->propertyId = $event->propertyId();
        $this->source = $event->source();
        $this->year = $event->year();
        $this->emissionFactorGroupId = $event->emissionFactorGroupId();
        $this->contactEmail = $event->contactEmail();
        $this->contactName = $event->contactName();
        $this->contactLocale = $event->contactLocale();
        $this->token = $event->token();
        $this->status = ProviderContributionStatus::Pending;
        $this->registeredAt = $event->occurredOn();
    }

    public function applyOperatorContributionUserLinked(OperatorContributionUserLinked $event): void
    {
        $this->userId = $event->userId();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOperatorContributionConsentGranted(OperatorContributionConsentGranted $event): void
    {
        $this->consentProof = $event->consentProof();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOperatorContributionContactUpdated(OperatorContributionContactUpdated $event): void
    {
        $this->contactName = $event->contactName();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOperatorContributionMarkedAsPrecalculated(OperatorContributionMarkedAsPrecalculated $event): void
    {
        $this->precalculated = true;
        $this->resetAnswer();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOperatorContributionUnmarkedAsPrecalculated(OperatorContributionUnmarkedAsPrecalculated $event): void
    {
        $this->precalculated = false;
        $this->resetAnswer();
        $this->updatedAt = $event->occurredOn();
    }

    private function resetAnswer(): void
    {
        if ($this->status->isAnswered()) {
            $this->status = ProviderContributionStatus::Pending;
        }

        $this->answeredAt = null;
    }

    public function applyOperatorContributionActivityAdded(OperatorContributionActivityAdded $event): void
    {
        $activity = new OperatorContributionActivity(
            id: OperatorContributionActivityId::generate(),
            contribution: $this,
            activityId: $event->activityId(),
            scope: $event->scope(),
            source: $event->source(),
            addedAt: $event->occurredOn(),
        );

        if (is_array($this->activities)) {
            $this->activities[] = $activity;
        } elseif ($this->activities instanceof Collection) {
            $this->activities->add($activity);
        }
    }

    public function applyOperatorContributionActivityRemoved(OperatorContributionActivityRemoved $event): void
    {
        foreach ($this->activities as $key => $activity) {
            if (!$activity->activityId()->equals($event->activityId())) {
                continue;
            }

            if (is_array($this->activities)) {
                unset($this->activities[$key]);
            } elseif ($this->activities instanceof Collection) {
                $this->activities->removeElement($activity);
            }

            return;
        }
    }

    public function applyOperatorContributionAnswered(OperatorContributionAnswered $event): void
    {
        $this->status = ProviderContributionStatus::Answered;
        $this->answeredAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOperatorContributionIgnored(OperatorContributionIgnored $event): void
    {
        $this->status = ProviderContributionStatus::Ignored;
        $this->ignoredAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOperatorContributionUnignored(OperatorContributionUnignored $event): void
    {
        $this->status = $this->answeredAt instanceof \DateTimeImmutable ? ProviderContributionStatus::Answered : ProviderContributionStatus::Pending;
        $this->ignoredAt = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOperatorContributionNotificationResent(OperatorContributionNotificationResent $event): void
    {
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOperatorContributionRemoved(OperatorContributionRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    private static function assertValidContactName(?string $contactName): ?string
    {
        $contactName = null !== $contactName ? trim($contactName) : null;

        if ('' === $contactName) {
            return null;
        }

        if (null !== $contactName && mb_strlen($contactName) > self::MAX_CONTACT_NAME_LENGTH) {
            throw new \InvalidArgumentException(sprintf('The operator contact name cannot exceed %d characters', self::MAX_CONTACT_NAME_LENGTH));
        }

        return $contactName;
    }

    private function removeActivity(ActivityId $activityId): void
    {
        foreach ($this->activities as $activity) {
            if (!$activity->activityId()->equals($activityId)) {
                continue;
            }

            $this->recordApplyAndPublish(
                new OperatorContributionActivityRemoved(
                    contributionId: $this->id,
                    activityId: $activityId,
                    scope: $activity->scope(),
                    source: $activity->source(),
                ),
            );

            return;
        }
    }
}
