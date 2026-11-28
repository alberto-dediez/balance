<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class Contest implements Translatable, \Stringable
{
    use AggregateRoot {
        publish as protected publishEvent;
    }

    /** @var iterable<ContestTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<ContestHost>|Collection<int, ContestHost> */
    private iterable $hosts = [];

    /** @var iterable<ContestAllowedCountry>|Collection<int, ContestAllowedCountry> */
    private iterable $allowedCountries = [];

    /** @param array<DomainEvent> $events */
    public function __construct(
        private ContestId $id,
        private ?AccountId $accountId = null,
        private ContestParticipationMode $participationMode = ContestParticipationMode::Calculation,
        private ContestAwardMode $awardMode = ContestAwardMode::Draw,
        private ?int $winnersCount = 1,
        private bool $platformDraw = false,
        private bool $adultsOnly = true,
        private string $name = '',
        private string $description = '',
        private string $prizeDescription = '',
        private string $terms = '',
        private string $organizerName = '',
        private string $organizerTaxId = '',
        private string $organizerContactEmail = '',
        private ?string $privacyPolicyUrl = null,
        private \DateTimeImmutable $startsAt = new \DateTimeImmutable('NOW'),
        private \DateTimeImmutable $endsAt = new \DateTimeImmutable('NOW'),
        private ContestStatus $status = ContestStatus::Draft,
        private int $confirmedEntries = 0,
        private ?UserId $responsibilityAcceptedBy = null,
        private ?\DateTimeImmutable $responsibilityAcceptedAt = null,
        private ?\DateTimeImmutable $publishedAt = null,
        private ?\DateTimeImmutable $closedAt = null,
        private ?\DateTimeImmutable $resolvedAt = null,
        private ?UserId $drawnBy = null,
        private ?int $drawCandidates = null,
        private int $redrawnPrizes = 0,
        private ?\DateTimeImmutable $personalDataPurgedAt = null,
        private ?UserId $personalDataPurgedBy = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): ContestId
    {
        return $this->id;
    }

    public function accountId(): ?AccountId
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

    public function name(?Locale $locale = null): string
    {
        return $this->translationIn($locale)?->name() ?? $this->name;
    }

    public function description(?Locale $locale = null): string
    {
        return $this->translationIn($locale)?->description() ?? $this->description;
    }

    public function prizeDescription(?Locale $locale = null): string
    {
        return $this->translationIn($locale)?->prizeDescription() ?? $this->prizeDescription;
    }

    public function terms(?Locale $locale = null): string
    {
        return $this->translationIn($locale)?->terms() ?? $this->terms;
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

    public function status(): ContestStatus
    {
        return $this->status;
    }

    public function confirmedEntries(): int
    {
        return $this->confirmedEntries;
    }

    public function responsibilityAcceptedBy(): ?UserId
    {
        return $this->responsibilityAcceptedBy;
    }

    public function responsibilityAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->responsibilityAcceptedAt;
    }

    public function publishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function closedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function resolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function drawnBy(): ?UserId
    {
        return $this->drawnBy;
    }

    public function drawCandidates(): ?int
    {
        return $this->drawCandidates;
    }

    public function redrawnPrizes(): int
    {
        return $this->redrawnPrizes;
    }

    public function personalDataPurgedAt(): ?\DateTimeImmutable
    {
        return $this->personalDataPurgedAt;
    }

    public function personalDataPurgedBy(): ?UserId
    {
        return $this->personalDataPurgedBy;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function isDraft(): bool
    {
        return ContestStatus::Draft === $this->status;
    }

    public function isPublished(): bool
    {
        return ContestStatus::Published === $this->status;
    }

    public function isClosed(): bool
    {
        return ContestStatus::Closed === $this->status;
    }

    public function isResolved(): bool
    {
        return ContestStatus::Resolved === $this->status;
    }

    public function isPersonalDataPurged(): bool
    {
        return null !== $this->personalDataPurgedAt;
    }

    public function isDrawn(): bool
    {
        return null !== $this->drawnBy;
    }

    /** @return list<ContestHostReference> */
    public function hostReferences(): array
    {
        return array_map(static fn(ContestHost $host): ContestHostReference => $host->reference(), $this->hosts());
    }

    public function hasHost(ContestHostReference $host): bool
    {
        foreach ($this->hostReferences() as $reference) {
            if ($reference->equals($host)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function allowedCountryIsos(): array
    {
        return array_map(static fn(ContestAllowedCountry $country): string => $country->countryIso(), $this->allowedCountries());
    }

    public function allowsCountry(string $iso): bool
    {
        $allowed = $this->allowedCountryIsos();

        return [] === $allowed || in_array(strtoupper(trim($iso)), $allowed, true);
    }

    public function acceptsEntries(\DateTimeImmutable $now): bool
    {
        return $this->isPublished() && $this->startsAt <= $now && $now <= $this->endsAt;
    }

    public function requiresPlatformDraw(): bool
    {
        return $this->awardMode->isDraw() && $this->platformDraw;
    }

    public function awardsOnConfirmation(int $currentWinners): bool
    {
        return $this->awardMode->isEveryone()
            || ($this->awardMode->isFirstEntrants() && $currentWinners < (int) $this->winnersCount);
    }

    public function hasAwardedEveryPrize(int $currentWinners): bool
    {
        return $this->awardMode->isFirstEntrants() && $currentWinners >= (int) $this->winnersCount;
    }

    public function reassignsPrizes(): bool
    {
        return $this->awardMode->isFirstEntrants()
            || ($this->requiresPlatformDraw() && $this->isResolved());
    }

    public function termsVersion(): string
    {
        return (string) $this->id . ':' . ($this->publishedAt ?? $this->registeredAt)->format('YmdHis');
    }

    /**
     * @param list<ContestHostReference> $hosts
     * @param list<string>               $allowedCountries
     */
    public function register(
        AccountId $accountId,
        ContestParticipationMode $participationMode,
        ContestAwardMode $awardMode,
        ?int $winnersCount,
        bool $platformDraw,
        bool $adultsOnly,
        string $name,
        string $description,
        string $prizeDescription,
        string $terms,
        string $organizerName,
        string $organizerTaxId,
        string $organizerContactEmail,
        ?string $privacyPolicyUrl,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
        array $hosts,
        array $allowedCountries,
    ): void {
        $allowedCountries = $this->normalizedCountries($allowedCountries);
        $this->guardConfiguration($name, $awardMode, $winnersCount, $platformDraw, $startsAt, $endsAt, $hosts);

        $this->recordApplyAndPublish(
            new ContestRegistered(
                contestId: $this->id(),
                accountId: $accountId,
                participationMode: $participationMode,
                awardMode: $awardMode,
                winnersCount: $awardMode->needsWinnersCount() ? $winnersCount : null,
                platformDraw: $platformDraw,
                adultsOnly: $adultsOnly,
                name: trim($name),
                description: $description,
                prizeDescription: trim($prizeDescription),
                terms: $terms,
                organizerName: trim($organizerName),
                organizerTaxId: trim($organizerTaxId),
                organizerContactEmail: trim($organizerContactEmail),
                privacyPolicyUrl: $this->normalizedUrl($privacyPolicyUrl),
                startsAt: $startsAt,
                endsAt: $endsAt,
                hosts: $hosts,
                allowedCountries: $allowedCountries,
            ),
        );
    }

    /**
     * @param list<ContestHostReference> $hosts
     * @param list<string>               $allowedCountries
     */
    public function update(
        ContestParticipationMode $participationMode,
        ContestAwardMode $awardMode,
        ?int $winnersCount,
        bool $platformDraw,
        bool $adultsOnly,
        string $name,
        string $description,
        string $prizeDescription,
        string $terms,
        string $organizerName,
        string $organizerTaxId,
        string $organizerContactEmail,
        ?string $privacyPolicyUrl,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
        array $hosts,
        array $allowedCountries,
        ?Locale $locale = null,
    ): void {
        $allowedCountries = $this->normalizedCountries($allowedCountries);
        $winnersCount = $awardMode->needsWinnersCount() ? $winnersCount : null;
        $translating = $locale instanceof Locale;

        $this->guardConfiguration($translating ? $this->name : $name, $awardMode, $winnersCount, $platformDraw, $startsAt, $endsAt, $hosts);
        $this->guardEditable($participationMode, $awardMode, $winnersCount, $platformDraw, $adultsOnly, $startsAt, $endsAt, $hosts, $allowedCountries);

        $this->recordApplyAndPublish(
            new ContestUpdated(
                contestId: $this->id(),
                participationMode: $participationMode,
                awardMode: $awardMode,
                winnersCount: $winnersCount,
                platformDraw: $platformDraw,
                adultsOnly: $adultsOnly,
                name: $translating ? $this->name : trim($name),
                description: $translating ? $this->description : $description,
                prizeDescription: $translating ? $this->prizeDescription : trim($prizeDescription),
                terms: $translating ? $this->terms : $terms,
                organizerName: trim($organizerName),
                organizerTaxId: trim($organizerTaxId),
                organizerContactEmail: trim($organizerContactEmail),
                privacyPolicyUrl: $this->normalizedUrl($privacyPolicyUrl),
                startsAt: $startsAt,
                endsAt: $endsAt,
                hosts: $hosts,
                allowedCountries: $allowedCountries,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate(trim($name), $description, trim($prizeDescription), $terms, $locale);
        }
    }

    public function translate(string $name, string $description, string $prizeDescription, string $terms, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new ContestTranslated(
                contestId: $this->id(),
                name: $name,
                description: $description,
                prizeDescription: $prizeDescription,
                terms: $terms,
                locale: $locale,
            ),
        );
    }

    public function publish(UserId $by, \DateTimeImmutable $at): void
    {
        if (!$this->isDraft()) {
            throw new ContestNotPublishableException('Solo se puede publicar un concurso en borrador.');
        }

        if ([] === $this->hosts()) {
            throw new ContestNotPublishableException('El concurso debe estar asociado al menos a una calculadora.');
        }

        if ('' === trim($this->name) || '' === trim($this->prizeDescription) || '' === trim($this->terms)) {
            throw new ContestNotPublishableException('El concurso debe tener nombre, premio y bases.');
        }

        if ('' === trim($this->organizerName) || '' === trim($this->organizerTaxId) || '' === trim($this->organizerContactEmail)) {
            throw new ContestNotPublishableException('El concurso debe tener nombre, NIF y email del organizador.');
        }

        if ($this->endsAt <= $this->startsAt) {
            throw new ContestNotPublishableException('La fecha de fin debe ser posterior a la de inicio.');
        }

        $this->recordApplyAndPublish(
            new ContestPublished(
                contestId: $this->id(),
                publishedBy: $by,
                occurredOn: $at,
            ),
        );
    }

    public function close(\DateTimeImmutable $at): void
    {
        if (!$this->isPublished()) {
            throw new ContestNotEditableException('Solo se puede cerrar un concurso publicado.');
        }

        $this->recordApplyAndPublish(
            new ContestClosed(
                contestId: $this->id(),
                occurredOn: $at,
            ),
        );

        if (!$this->requiresPlatformDraw()) {
            $this->recordApplyAndPublish(
                new ContestResolved(
                    contestId: $this->id(),
                    drawnBy: null,
                    drawCandidates: null,
                    occurredOn: $at,
                ),
            );
        }
    }

    public function resolveByDraw(UserId $by, int $candidates, \DateTimeImmutable $at): void
    {
        if ($this->isResolved()) {
            throw new ContestAlreadyResolvedException();
        }

        if (!$this->isClosed()) {
            throw new ContestNotEditableException('Solo se puede sortear un concurso cerrado.');
        }

        $this->recordApplyAndPublish(
            new ContestResolved(
                contestId: $this->id(),
                drawnBy: $by,
                drawCandidates: $candidates,
                occurredOn: $at,
            ),
        );
    }

    public function countConfirmedEntry(): int
    {
        $entryNumber = $this->confirmedEntries + 1;

        $this->recordApplyAndPublish(
            new ContestEntryCounted(
                contestId: $this->id(),
                entryNumber: $entryNumber,
            ),
        );

        return $entryNumber;
    }

    public function recordPrizeRedrawn(\DateTimeImmutable $at): void
    {
        if (!$this->isResolved() || !$this->requiresPlatformDraw()) {
            throw new ContestNotEditableException('Solo se vuelve a sortear un premio en un concurso ya sorteado por la plataforma.');
        }

        $this->recordApplyAndPublish(
            new ContestPrizeRedrawn(
                contestId: $this->id(),
                occurredOn: $at,
            ),
        );
    }

    public function purgePersonalData(UserId $by, \DateTimeImmutable $at): void
    {
        if (!$this->isClosed() && !$this->isResolved()) {
            throw new ContestNotEditableException('Solo se pueden purgar los datos de un concurso cerrado.');
        }

        if ($this->isPersonalDataPurged()) {
            throw new ContestNotEditableException('Los datos personales de este concurso ya se han purgado.');
        }

        $this->recordApplyAndPublish(
            new ContestPersonalDataPurged(
                contestId: $this->id(),
                purgedBy: $by,
                occurredOn: $at,
            ),
        );
    }

    public function remove(): void
    {
        if (!$this->isDraft()) {
            throw new ContestNotRemovableException();
        }

        $this->recordApplyAndPublish(
            new ContestRemoved(
                contestId: $this->id(),
            ),
        );
    }

    public function applyContestRegistered(ContestRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
        $this->applyConfiguration(
            $event->participationMode(),
            $event->awardMode(),
            $event->winnersCount(),
            $event->platformDraw(),
            $event->adultsOnly(),
            $event->name(),
            $event->description(),
            $event->prizeDescription(),
            $event->terms(),
            $event->organizerName(),
            $event->organizerTaxId(),
            $event->organizerContactEmail(),
            $event->privacyPolicyUrl(),
            $event->startsAt(),
            $event->endsAt(),
            $event->hosts(),
            $event->allowedCountries(),
        );
    }

    public function applyContestUpdated(ContestUpdated $event): void
    {
        $this->applyConfiguration(
            $event->participationMode(),
            $event->awardMode(),
            $event->winnersCount(),
            $event->platformDraw(),
            $event->adultsOnly(),
            $event->name(),
            $event->description(),
            $event->prizeDescription(),
            $event->terms(),
            $event->organizerName(),
            $event->organizerTaxId(),
            $event->organizerContactEmail(),
            $event->privacyPolicyUrl(),
            $event->startsAt(),
            $event->endsAt(),
            $event->hosts(),
            $event->allowedCountries(),
        );
    }

    public function applyContestTranslated(ContestTranslated $event): void
    {
        $this->translations()->add(
            new ContestTranslation(
                name: $event->name(),
                description: $event->description(),
                prizeDescription: $event->prizeDescription(),
                terms: $event->terms(),
                locale: $event->locale(),
                translatable: $this,
            ),
        );
    }

    public function applyContestPublished(ContestPublished $event): void
    {
        $this->status = ContestStatus::Published;
        $this->responsibilityAcceptedBy = $event->publishedBy();
        $this->responsibilityAcceptedAt = $event->occurredOn();
        $this->publishedAt = $event->occurredOn();
    }

    public function applyContestClosed(ContestClosed $event): void
    {
        $this->status = ContestStatus::Closed;
        $this->closedAt = $event->occurredOn();
    }

    public function applyContestResolved(ContestResolved $event): void
    {
        $this->status = ContestStatus::Resolved;
        $this->resolvedAt = $event->occurredOn();
        $this->drawnBy = $event->drawnBy();
        $this->drawCandidates = $event->drawCandidates();
    }

    public function applyContestEntryCounted(ContestEntryCounted $event): void
    {
        $this->confirmedEntries = $event->entryNumber();
    }

    public function applyContestPrizeRedrawn(ContestPrizeRedrawn $event): void
    {
        ++$this->redrawnPrizes;
    }

    public function applyContestPersonalDataPurged(ContestPersonalDataPurged $event): void
    {
        $this->personalDataPurgedAt = $event->occurredOn();
        $this->personalDataPurgedBy = $event->purgedBy();
    }

    public function applyContestRemoved(ContestRemoved $event): void {}

    public function __toString(): string
    {
        return $this->name();
    }

    protected function recordApplyAndPublish(DomainEvent $event): void
    {
        $this->record($event);
        $this->apply($event);
        $this->publishEvent($event);
    }

    /**
     * @param list<ContestHostReference> $hosts
     * @param list<string>               $allowedCountries
     */
    private function applyConfiguration(
        ContestParticipationMode $participationMode,
        ContestAwardMode $awardMode,
        ?int $winnersCount,
        bool $platformDraw,
        bool $adultsOnly,
        string $name,
        string $description,
        string $prizeDescription,
        string $terms,
        string $organizerName,
        string $organizerTaxId,
        string $organizerContactEmail,
        ?string $privacyPolicyUrl,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
        array $hosts,
        array $allowedCountries,
    ): void {
        $this->participationMode = $participationMode;
        $this->awardMode = $awardMode;
        $this->winnersCount = $winnersCount;
        $this->platformDraw = $platformDraw;
        $this->adultsOnly = $adultsOnly;
        $this->name = $name;
        $this->description = $description;
        $this->prizeDescription = $prizeDescription;
        $this->terms = $terms;
        $this->organizerName = $organizerName;
        $this->organizerTaxId = $organizerTaxId;
        $this->organizerContactEmail = $organizerContactEmail;
        $this->privacyPolicyUrl = $privacyPolicyUrl;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;

        $this->hosts = $this->synchronizedChildren(
            $this->hosts,
            array_map(static fn(ContestHostReference $host): string => $host->key(), $hosts),
            static fn(ContestHost $host): string => $host->reference()->key(),
            fn(string $key): ContestHost => new ContestHost(
                id: ContestHostId::generate(),
                contest: $this,
                hostType: ContestHostReference::fromKey($key)->hostType,
                hostId: ContestHostReference::fromKey($key)->hostId,
            ),
        );

        $this->allowedCountries = $this->synchronizedChildren(
            $this->allowedCountries,
            $allowedCountries,
            static fn(ContestAllowedCountry $country): string => $country->countryIso(),
            fn(string $iso): ContestAllowedCountry => new ContestAllowedCountry(
                id: ContestAllowedCountryId::generate(),
                contest: $this,
                countryIso: $iso,
            ),
        );
    }

    /** @param list<ContestHostReference> $hosts */
    private function guardConfiguration(
        string $name,
        ContestAwardMode $awardMode,
        ?int $winnersCount,
        bool $platformDraw,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
        array $hosts,
    ): void {
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('El nombre del concurso es obligatorio.');
        }

        if ($endsAt <= $startsAt) {
            throw new \InvalidArgumentException('La fecha de fin debe ser posterior a la de inicio.');
        }

        if ($awardMode->needsWinnersCount() && (null === $winnersCount || $winnersCount < 1)) {
            throw new \InvalidArgumentException('El número de premiados debe ser al menos 1.');
        }

        if ($platformDraw && !$awardMode->isDraw()) {
            throw new \InvalidArgumentException('El sorteo de la plataforma solo está disponible en los concursos por sorteo.');
        }

        $keys = array_map(static fn(ContestHostReference $host): string => $host->key(), $hosts);

        if (count($keys) !== count(array_unique($keys))) {
            throw new \InvalidArgumentException('Hay calculadoras repetidas.');
        }
    }

    /**
     * @param list<ContestHostReference> $hosts
     * @param list<string>               $allowedCountries
     */
    private function guardEditable(
        ContestParticipationMode $participationMode,
        ContestAwardMode $awardMode,
        ?int $winnersCount,
        bool $platformDraw,
        bool $adultsOnly,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
        array $hosts,
        array $allowedCountries,
    ): void {
        if ($this->isDraft()) {
            return;
        }

        if (!$this->isPublished()) {
            throw new ContestNotEditableException();
        }

        $current = $this->allowedCountryIsos();
        $wanted = $allowedCountries;
        sort($current);
        sort($wanted);

        if (
            $participationMode !== $this->participationMode
            || $awardMode !== $this->awardMode
            || $winnersCount !== $this->winnersCount
            || $platformDraw !== $this->platformDraw
            || $adultsOnly !== $this->adultsOnly
            || $startsAt != $this->startsAt
            || $current !== $wanted
        ) {
            throw new ContestNotEditableException('En un concurso publicado solo se pueden cambiar los textos, la fecha de fin, el organizador y las calculadoras.');
        }

        if ($endsAt != $this->endsAt && $endsAt <= new \DateTimeImmutable('NOW')) {
            throw new ContestNotEditableException('La nueva fecha de fin de un concurso publicado debe ser futura.');
        }

        if ([] === $hosts) {
            throw new ContestNotEditableException('Un concurso publicado debe mantener al menos una calculadora.');
        }
    }

    /**
     * @param list<string> $countries
     *
     * @return list<string>
     */
    private function normalizedCountries(array $countries): array
    {
        $normalized = [];

        foreach ($countries as $country) {
            $iso = trim($country);

            if (1 !== preg_match('/^[A-Z]{2}$/', $iso)) {
                throw new \InvalidArgumentException(sprintf('El código de país «%s» no es un ISO alfa-2 válido.', $country));
            }

            if (in_array($iso, $normalized, true)) {
                throw new \InvalidArgumentException('Hay países repetidos.');
            }

            $normalized[] = $iso;
        }

        return $normalized;
    }

    private function normalizedUrl(?string $url): ?string
    {
        $url = null !== $url ? trim($url) : '';

        return '' === $url ? null : $url;
    }

    /** @return list<ContestHost> */
    private function hosts(): array
    {
        return array_values($this->hosts instanceof Collection ? $this->hosts->toArray() : (array) $this->hosts);
    }

    /** @return list<ContestAllowedCountry> */
    private function allowedCountries(): array
    {
        return array_values(
            $this->allowedCountries instanceof Collection
                ? $this->allowedCountries->toArray()
                : (array) $this->allowedCountries,
        );
    }

    /**
     * @template TChild of object
     *
     * @param iterable<TChild>|Collection<int, TChild> $children
     * @param list<string>                             $keys
     * @param callable(TChild): string                 $keyOf
     * @param callable(string): TChild                 $create
     *
     * @return iterable<TChild>|Collection<int, TChild>
     */
    private function synchronizedChildren(iterable $children, array $keys, callable $keyOf, callable $create): iterable
    {
        $kept = [];

        if ($children instanceof Collection) {
            foreach ($children->toArray() as $child) {
                $childKey = $keyOf($child);

                if (!in_array($childKey, $keys, true) || in_array($childKey, $kept, true)) {
                    $children->removeElement($child);

                    continue;
                }

                $kept[] = $childKey;
            }

            foreach ($keys as $key) {
                if (!in_array($key, $kept, true)) {
                    $children->add($create($key));
                }
            }

            return $children;
        }

        $result = [];

        foreach ($children as $child) {
            $childKey = $keyOf($child);

            if (in_array($childKey, $keys, true) && !in_array($childKey, $kept, true)) {
                $result[] = $child;
                $kept[] = $childKey;
            }
        }

        foreach ($keys as $key) {
            if (!in_array($key, $kept, true)) {
                $result[] = $create($key);
            }
        }

        return $result;
    }

    private function translationIn(?Locale $locale): ?ContestTranslation
    {
        $translation = $this->translations()->getTranslationFor($locale ?? LocaleGetter::locale());

        return $translation instanceof ContestTranslation ? $translation : null;
    }
}
