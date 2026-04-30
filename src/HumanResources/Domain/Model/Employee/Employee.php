<?php

declare(strict_types=1);

namespace App\HumanResources\Domain\Model\Employee;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Address;
use App\Shared\Domain\Model\AggregateRoot;

class Employee implements \Stringable
{
    use AggregateRoot;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private EmployeeId $id,
        private string $name = '',
        private string $email = '',
        private ?string $phone = null,
        private ?Address $address = null,
        private AccountId $accountId = new AccountId(''),
        private ?UserId $userId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): EmployeeId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function address(): ?Address
    {
        return $this->address;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function userId(): ?UserId
    {
        return $this->userId;
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

    public function register(
        string $name,
        string $email,
        ?string $phone,
        ?Address $address,
        AccountId $accountId,
        ?UserId $userId,
    ): void {
        $this->recordApplyAndPublish(
            new EmployeeRegistered(
                employeeId: $this->id(),
                name: $name,
                email: $email,
                phone: $phone,
                address: $address,
                accountId: $accountId,
                userId: $userId,
            ),
        );
    }

    public function update(
        string $name,
        string $email,
        ?string $phone,
        ?Address $address,
        AccountId $accountId,
        ?UserId $userId,
    ): void {
        $this->recordApplyAndPublish(
            new EmployeeUpdated(
                employeeId: $this->id(),
                name: $name,
                email: $email,
                phone: $phone,
                address: $address,
                accountId: $accountId,
                userId: $userId,
            ),
        );
    }

    public function linkUser(UserId $userId): void
    {
        $this->recordApplyAndPublish(
            new EmployeeAndUserLinked(
                employeeId: $this->id(),
                userId: $userId,
            ),
        );
    }

    public function unlinkUser(): void
    {
        $this->recordApplyAndPublish(
            new EmployeeAndUserUnlinked(
                employeeId: $this->id(),
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new EmployeeRemoved(
                employeeId: $this->id(),
            ),
        );
    }

    public function applyEmployeeRegistered(EmployeeRegistered $event): void
    {
        $this->name = $event->name();
        $this->email = $event->email();
        $this->phone = $event->phone();
        $this->address = $event->address();
        $this->accountId = $event->accountId();
        $this->userId = $event->userId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEmployeeUpdated(EmployeeUpdated $event): void
    {
        $this->name = $event->name();
        $this->email = $event->email();
        $this->phone = $event->phone();
        $this->address = $event->address();
        $this->accountId = $event->accountId();
        $this->userId = $event->userId();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEmployeeAndUserLinked(EmployeeAndUserLinked $event): void
    {
        $this->userId = $event->userId();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEmployeeAndUserUnlinked(EmployeeAndUserUnlinked $event): void
    {
        $this->userId = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEmployeeRemoved(EmployeeRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
