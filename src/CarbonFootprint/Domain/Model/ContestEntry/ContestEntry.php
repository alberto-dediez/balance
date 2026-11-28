<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ContestEntry;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostType;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\Locale;

class ContestEntry implements \Stringable
{
    use AggregateRoot;

    private const int REASON_MAX_LENGTH = 255;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private ContestEntryId $id,
        private ?ContestId $contestId = null,
        private ?AccountId $accountId = null,
        private ContestHostType $hostType = ContestHostType::Event,
        private string $hostId = '',
        private ProjectContributionOrigin $origin = ProjectContributionOrigin::AttendeeSurveyResponse,
        private string $originId = '',
        private ?string $projectContributionId = null,
        private string $name = '',
        private ?string $email = null,
        private ?string $phone = null,
        private ?string $country = null,
        private Locale $locale = Locale::Spanish,
        private ?ConsentProof $consentProof = null,
        private ContestEntryStatus $status = ContestEntryStatus::PendingPayment,
        private ?int $entryNumber = null,
        private bool $winner = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $confirmedAt = null,
        private ?\DateTimeImmutable $awardedAt = null,
        private ?\DateTimeImmutable $disqualifiedAt = null,
        private ?UserId $disqualifiedBy = null,
        private ?string $disqualificationReason = null,
        private ?\DateTimeImmutable $personalDataPurgedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): ContestEntryId
    {
        return $this->id;
    }

    public function contestId(): ?ContestId
    {
        return $this->contestId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function hostType(): ContestHostType
    {
        return $this->hostType;
    }

    public function hostId(): string
    {
        return $this->hostId;
    }

    public function host(): ContestHostReference
    {
        return new ContestHostReference($this->hostType, $this->hostId);
    }

    public function origin(): ProjectContributionOrigin
    {
        return $this->origin;
    }

    public function originId(): string
    {
        return $this->originId;
    }

    public function projectContributionId(): ?string
    {
        return $this->projectContributionId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function country(): ?string
    {
        return $this->country;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function consentProof(): ?ConsentProof
    {
        return $this->consentProof;
    }

    public function status(): ContestEntryStatus
    {
        return $this->status;
    }

    public function entryNumber(): ?int
    {
        return $this->entryNumber;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function confirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function awardedAt(): ?\DateTimeImmutable
    {
        return $this->awardedAt;
    }

    public function disqualifiedAt(): ?\DateTimeImmutable
    {
        return $this->disqualifiedAt;
    }

    public function disqualifiedBy(): ?UserId
    {
        return $this->disqualifiedBy;
    }

    public function disqualificationReason(): ?string
    {
        return $this->disqualificationReason;
    }

    public function personalDataPurgedAt(): ?\DateTimeImmutable
    {
        return $this->personalDataPurgedAt;
    }

    public function isConfirmed(): bool
    {
        return ContestEntryStatus::Confirmed === $this->status;
    }

    public function isPendingPayment(): bool
    {
        return ContestEntryStatus::PendingPayment === $this->status;
    }

    public function isDisqualified(): bool
    {
        return ContestEntryStatus::Disqualified === $this->status;
    }

    public function isWinner(): bool
    {
        return $this->winner;
    }

    public function isPurged(): bool
    {
        return null !== $this->personalDataPurgedAt;
    }

    public function isEligible(): bool
    {
        return $this->isConfirmed() && !$this->isPurged();
    }

    public function register(
        ContestId $contestId,
        AccountId $accountId,
        ContestHostReference $host,
        ProjectContributionOrigin $origin,
        string $originId,
        ?string $projectContributionId,
        string $name,
        string $email,
        ?string $phone,
        string $country,
        Locale $locale,
        ConsentProof $consentProof,
        \DateTimeImmutable $at,
    ): void {
        $email = self::normalizedEmail($email);
        $this->guardParticipantData($name, $email, $country);

        $this->recordApplyAndPublish(
            new ContestEntryRegistered(
                contestEntryId: $this->id(),
                contestId: $contestId,
                accountId: $accountId,
                hostType: $host->hostType,
                hostId: $host->hostId,
                origin: $origin,
                originId: $originId,
                projectContributionId: $projectContributionId,
                name: trim($name),
                email: $email,
                phone: self::normalizedPhone($phone),
                country: strtoupper(trim($country)),
                locale: $locale,
                consentProof: $consentProof,
                occurredOn: $at,
            ),
        );
    }

    public function renewPendingPayment(
        string $projectContributionId,
        string $name,
        string $email,
        ?string $phone,
        string $country,
        Locale $locale,
        ConsentProof $consentProof,
        \DateTimeImmutable $at,
    ): void {
        if (!$this->isPendingPayment()) {
            throw new ContestEntryNotPendingPaymentException();
        }

        $email = self::normalizedEmail($email);
        $this->guardParticipantData($name, $email, $country);

        $this->recordApplyAndPublish(
            new ContestEntryPaymentRenewed(
                contestEntryId: $this->id(),
                projectContributionId: $projectContributionId,
                name: trim($name),
                email: $email,
                phone: self::normalizedPhone($phone),
                country: strtoupper(trim($country)),
                locale: $locale,
                consentProof: $consentProof,
                occurredOn: $at,
            ),
        );
    }

    public function confirm(int $entryNumber, \DateTimeImmutable $at): void
    {
        if (!$this->isPendingPayment()) {
            return;
        }

        $this->recordApplyAndPublish(
            new ContestEntryConfirmed(
                contestEntryId: $this->id(),
                entryNumber: $entryNumber,
                occurredOn: $at,
            ),
        );
    }

    public function award(\DateTimeImmutable $at): void
    {
        if (!$this->isConfirmed()) {
            throw new \DomainException('Solo se puede premiar una inscripción confirmada.');
        }

        $this->recordApplyAndPublish(
            new ContestEntryAwarded(
                contestEntryId: $this->id(),
                occurredOn: $at,
            ),
        );
    }

    public function disqualify(UserId $by, string $reason, \DateTimeImmutable $at): void
    {
        if (!$this->isConfirmed() || $this->isPurged()) {
            throw new ContestEntryNotDisqualifiableException();
        }

        $reason = trim($reason);

        if ('' === $reason) {
            throw new \InvalidArgumentException('El motivo de la descalificación es obligatorio.');
        }

        $this->recordApplyAndPublish(
            new ContestEntryDisqualified(
                contestEntryId: $this->id(),
                disqualifiedBy: $by,
                reason: mb_substr($reason, 0, self::REASON_MAX_LENGTH),
                occurredOn: $at,
            ),
        );
    }

    public function purgePersonalData(\DateTimeImmutable $at): void
    {
        $this->recordApplyAndPublish(
            new ContestEntryPersonalDataPurged(
                contestEntryId: $this->id(),
                occurredOn: $at,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new ContestEntryRemoved(
                contestEntryId: $this->id(),
            ),
        );
    }

    public function applyContestEntryRegistered(ContestEntryRegistered $event): void
    {
        $this->contestId = $event->contestId();
        $this->accountId = $event->accountId();
        $this->hostType = $event->hostType();
        $this->hostId = $event->hostId();
        $this->origin = $event->origin();
        $this->originId = $event->originId();
        $this->projectContributionId = $event->projectContributionId();
        $this->name = $event->name();
        $this->email = $event->email();
        $this->phone = $event->phone();
        $this->country = $event->country();
        $this->locale = $event->locale();
        $this->consentProof = $event->consentProof();
        $this->status = ContestEntryStatus::PendingPayment;
        $this->registeredAt = $event->occurredOn();
    }

    public function applyContestEntryPaymentRenewed(ContestEntryPaymentRenewed $event): void
    {
        $this->projectContributionId = $event->projectContributionId();
        $this->name = $event->name();
        $this->email = $event->email();
        $this->phone = $event->phone();
        $this->country = $event->country();
        $this->locale = $event->locale();
        $this->consentProof = $event->consentProof();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyContestEntryConfirmed(ContestEntryConfirmed $event): void
    {
        $this->status = ContestEntryStatus::Confirmed;
        $this->entryNumber = $event->entryNumber();
        $this->confirmedAt = $event->occurredOn();
    }

    public function applyContestEntryAwarded(ContestEntryAwarded $event): void
    {
        $this->winner = true;
        $this->awardedAt = $event->occurredOn();
    }

    public function applyContestEntryDisqualified(ContestEntryDisqualified $event): void
    {
        $this->status = ContestEntryStatus::Disqualified;
        $this->winner = false;
        $this->disqualifiedAt = $event->occurredOn();
        $this->disqualifiedBy = $event->disqualifiedBy();
        $this->disqualificationReason = $event->reason();
    }

    public function applyContestEntryPersonalDataPurged(ContestEntryPersonalDataPurged $event): void
    {
        $this->name = '';
        $this->email = null;
        $this->phone = null;
        $this->country = null;
        $this->disqualificationReason = null;
        $this->personalDataPurgedAt = $event->occurredOn();

        if ($this->consentProof instanceof ConsentProof) {
            $this->consentProof = new ConsentProof(
                consentedAt: $this->consentProof->consentedAt(),
                policyVersion: $this->consentProof->policyVersion(),
                locale: $this->consentProof->locale(),
            );
        }
    }

    public function applyContestEntryRemoved(ContestEntryRemoved $event): void {}

    public function __toString(): string
    {
        return (string) $this->id;
    }

    private static function normalizedEmail(string $email): string
    {
        return mb_strtolower((string) preg_replace('/\s+/', '', $email));
    }

    private static function normalizedPhone(?string $phone): ?string
    {
        $phone = null !== $phone ? trim($phone) : '';

        return '' === $phone ? null : $phone;
    }

    private function guardParticipantData(string $name, string $email, string $country): void
    {
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('El nombre del participante es obligatorio.');
        }

        if ('' === $email) {
            throw new \InvalidArgumentException('El email del participante es obligatorio.');
        }

        if (false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El email del participante no es válido.');
        }

        if ('' === trim($country)) {
            throw new \InvalidArgumentException('El país del participante es obligatorio.');
        }

        if (1 !== preg_match('/^[A-Z]{2}$/', strtoupper(trim($country)))) {
            throw new \InvalidArgumentException('El país del participante no es válido.');
        }
    }
}
