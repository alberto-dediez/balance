<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

class ContestHost
{
    public function __construct(
        private ContestHostId $id,
        private Contest $contest,
        private ContestHostType $hostType,
        private string $hostId,
    ) {}

    public function id(): ContestHostId
    {
        return $this->id;
    }

    public function contest(): Contest
    {
        return $this->contest;
    }

    public function hostType(): ContestHostType
    {
        return $this->hostType;
    }

    public function hostId(): string
    {
        return $this->hostId;
    }

    public function reference(): ContestHostReference
    {
        return new ContestHostReference($this->hostType, $this->hostId);
    }
}
