<?php

namespace App\DataExchange\Domain\Model\Importation;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class Importation
{
    use AggregateRoot;

    public function __construct(
        protected ImportationId $id,
        protected string $name = '',
        protected ?AccountId $accountId = null,
        protected ?ImportationFile $file = null,
        array $events = [],
        protected ?\DateTimeImmutable $registeredAt = null,
    ) {

    }

    public function id(): ImportationId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function file(): ?ImportationFile
    {
        return $this->file;
    }

    public function register(string $name, ?AccountId $accountId)
    {
        $this->recordApplyAndPublish(
            new ImportationRegistered(
                importationId: $this->id(),
                name: $name,
                accountId: $accountId
            )
        );
    }

    public function applyImportationRegistered(ImportationRegistered $event)
    {
        $this->accountId = $event->accountId();
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
