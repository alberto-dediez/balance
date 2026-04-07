<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Protocol;

interface ProtocolRepository
{
    public function add(Protocol $protocol): void;

    public function remove(Protocol $protocol): void;

    public function find(ProtocolId $protocolId): ?Protocol;

    /** @return array<Protocol> */
    public function findByFilter(ProtocolFilter $filter): array;

    public function totalResultsByFilter(ProtocolFilter $filter): int;
}
