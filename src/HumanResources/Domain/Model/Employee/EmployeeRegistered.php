<?php

declare(strict_types=1);

namespace App\HumanResources\Domain\Model\Employee;

use App\HumanResources\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\Address;
use App\Shared\Domain\Model\AggregateRootId;

final class EmployeeRegistered extends DomainEvent
{
    protected static string $eventName = 'employeeRegistered';

    public function __construct(
        private EmployeeId $employeeId,
        private string $name,
        private string $email,
        private ?string $phone,
        private ?Address $address,
        private AccountId $accountId,
        private ?UserId $userId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function employeeId(): EmployeeId
    {
        return $this->employeeId;
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->employeeId();
    }

    public function payload(): array
    {
        return [
            'employeeId' => (string) $this->employeeId,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'street' => $this->address?->street(),
            'postalCode' => $this->address?->postalCode(),
            'city' => $this->address?->city(),
            'country' => $this->address?->country(),
            'accountId' => (string) $this->accountId,
            'userId' => $this->userId ? (string) $this->userId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $address = null;
        if ($payload['street'] || $payload['postalCode'] || $payload['city'] || $payload['country']) {
            $address = new Address(
                street: $payload['street'],
                postalCode: $payload['postalCode'],
                city: $payload['city'],
                country: $payload['country'],
            );
        }

        return new EmployeeRegistered(
            employeeId: new EmployeeId($payload['employeeId']),
            name: $payload['name'],
            email: $payload['email'],
            phone: $payload['phone'],
            address: $address,
            accountId: new AccountId($payload['accountId']),
            userId: $payload['userId'] ? new UserId($payload['userId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
