<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Account implements \Stringable
{
    use AggregateRoot;

    /**
     * @param DomainEvent[] $events
     */
    public function __construct(
        protected AccountId $id,
        protected string $name = '',
        protected bool $active = false,
        protected bool $removed = false,
        array $events = [],
        protected \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        protected ?\DateTimeImmutable $removedAt = null,
        protected ?EmissionFactorGroupId $emissionFactorGroupId = null,
        protected bool $consultant = false,
        protected ?AccountId $consultantAccountId = null,
        protected AccountReportPermissions $reportPermissions = new AccountReportPermissions(),
    ) {
        $this->events = $events;
    }

    public function id(): AccountId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function emissionFactorGroupId(): ?EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function isConsultant(): bool
    {
        return $this->consultant;
    }

    public function consultantAccountId(): ?AccountId
    {
        return $this->consultantAccountId;
    }

    public function reportPermissions(): AccountReportPermissions
    {
        return $this->reportPermissions;
    }

    public function register(string $name, bool $active, ?EmissionFactorGroupId $emissionFactorGroupId = null, bool $consultant = false): void
    {
        $this->recordApplyAndPublish(
            new AccountRegistered(
                accountId: $this->id(),
                name: $name,
                active: $active,
                emissionFactorGroupId: $emissionFactorGroupId,
                consultant: $consultant,
            ),
        );
    }

    public function changeName(string $name): void
    {
        $this->recordApplyAndPublish(
            new AccountNameChanged(
                accountId: $this->id(),
                name: $name,
            ),
        );
    }

    public function changeStatus(bool $active): void
    {
        $this->recordApplyAndPublish(
            new AccountStatusChanged(
                accountId: $this->id(),
                active: $active,
            ),
        );
    }

    public function changeEmissionFactorGroup(EmissionFactorGroupId $emissionFactorGroupId): void
    {
        $this->recordApplyAndPublish(
            new AccountEmissionFactorGroupChanged(
                accountId: $this->id(),
                emissionFactorGroupId: $emissionFactorGroupId,
            ),
        );
    }

    public function changeConsultantStatus(bool $consultant): void
    {
        if ($consultant && $this->consultantAccountId instanceof AccountId) {
            throw new ConsultantCannotHaveConsultantException($this->id());
        }

        $this->recordApplyAndPublish(
            new AccountConsultantStatusChanged(
                accountId: $this->id(),
                consultant: $consultant,
            ),
        );
    }

    public function changeConsultant(?AccountId $consultantAccountId): void
    {
        if ($consultantAccountId instanceof AccountId && $this->consultant) {
            throw new ConsultantCannotHaveConsultantException($this->id());
        }

        $this->recordApplyAndPublish(
            new AccountConsultantChanged(
                accountId: $this->id(),
                consultantAccountId: $consultantAccountId,
            ),
        );
    }

    public function changeReportPermissions(AccountReportPermissions $permissions): void
    {
        if ($permissions->equals($this->reportPermissions)) {
            return;
        }

        $this->recordApplyAndPublish(
            new AccountReportPermissionsChanged(
                accountId: $this->id(),
                canEditTexts: $permissions->canEditTexts(),
                autoReview: $permissions->autoReview(),
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new AccountRemoved(
                accountId: $this->id(),
            ),
        );
    }

    public function applyAccountRegistered(AccountRegistered $event): void
    {
        $this->name = $event->name();
        $this->active = $event->active();
        $this->emissionFactorGroupId = $event->emissionFactorGroupId();
        $this->consultant = $event->consultant();

        $this->registeredAt = $event->occurredOn();
    }

    public function applyAccountNameChanged(AccountNameChanged $event): void
    {
        $this->name = $event->name();
    }

    public function applyAccountStatusChanged(AccountStatusChanged $event): void
    {
        $this->active = $event->active();
    }

    public function applyAccountEmissionFactorGroupChanged(AccountEmissionFactorGroupChanged $event): void
    {
        $this->emissionFactorGroupId = $event->emissionFactorGroupId();
    }

    public function applyAccountConsultantStatusChanged(AccountConsultantStatusChanged $event): void
    {
        $this->consultant = $event->consultant();
    }

    public function applyAccountConsultantChanged(AccountConsultantChanged $event): void
    {
        $this->consultantAccountId = $event->consultantAccountId();
    }

    public function applyAccountReportPermissionsChanged(AccountReportPermissionsChanged $event): void
    {
        $this->reportPermissions = new AccountReportPermissions($event->canEditTexts(), $event->autoReview());
    }

    public function applyAccountRemoved(AccountRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = new \DateTimeImmutable('NOW');
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
