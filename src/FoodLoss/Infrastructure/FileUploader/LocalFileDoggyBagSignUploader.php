<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\FileUploader;

use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignUploader;
use App\Security\Domain\Model\Account\AccountId;

class LocalFileDoggyBagSignUploader implements DoggyBagSignUploader
{
    public function __construct(private readonly string $uploadsDir) {}

    public function upload(string $sourcePath, string $extension, AccountId $accountId): string
    {
        $fileName = uniqid() . '.' . $extension;
        $relativePath = 'doggy_bag_signs/' . $accountId . '/' . $fileName;
        $absoluteDestination = $this->uploadsDir . '/' . $relativePath;

        if (!is_dir(dirname($absoluteDestination))) {
            mkdir(dirname($absoluteDestination), 0755, true);
        }

        rename($sourcePath, $absoluteDestination);

        return $relativePath;
    }
}
