<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Type;

use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class LedgerEntryIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'ledger_entry_id';
    }

    public function getClassName(): string
    {
        return LedgerEntryId::class;
    }
}
