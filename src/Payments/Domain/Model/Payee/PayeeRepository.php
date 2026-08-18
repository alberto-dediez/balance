<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payee;

interface PayeeRepository
{
    public function add(Payee $payee): void;

    public function find(PayeeId $payeeId): ?Payee;

    public function findOneBySubject(PayeeType $type, string $subjectId): ?Payee;

    /** @return array<Payee> */
    public function findByFilter(PayeeFilter $filter): array;

    public function totalResultsByFilter(PayeeFilter $filter): int;
}
