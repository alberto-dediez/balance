<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Protocol;

use App\Security\Domain\Model\Account\AccountId;

interface ProtocolUploader
{
    public function upload(string $sourcePath, string $extension, AccountId $accountId): string;
}
