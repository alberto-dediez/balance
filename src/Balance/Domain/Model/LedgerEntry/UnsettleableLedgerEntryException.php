<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

class UnsettleableLedgerEntryException extends \RuntimeException
{
    public function __construct(LedgerEntryId $id)
    {
        parent::__construct(sprintf('El asiento %s no se puede liquidar.', (string) $id), 409);
    }
}
