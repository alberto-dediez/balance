<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web;

use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeId;

final class PayeeNames
{
    /** @var array<string, string> */
    private array $names = [];

    /** @param array<Payee> $payees */
    public function __construct(array $payees)
    {
        foreach ($payees as $payee) {
            $this->names[(string) $payee->id()] = $payee->legalName();
        }
    }

    public function of(?PayeeId $payeeId): string
    {
        if (!$payeeId instanceof PayeeId) {
            return '-';
        }

        return $this->names[(string) $payeeId] ?? '-';
    }
}
