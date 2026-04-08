<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagSign;

use App\Security\Domain\Model\Account\AccountId;

interface DoggyBagSignUploader
{
    public function upload(string $sourcePath, string $extension, AccountId $accountId): string;
}
