<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagException;

use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagException;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;

trait GetDoggyBagExceptionFromIdTrait
{
    private function getDoggyBagExceptionFromId(string $doggyBagExceptionId, DoggyBagExceptionRepository $repository): DoggyBagException
    {
        $doggyBagException = $repository->find(new DoggyBagExceptionId($doggyBagExceptionId));

        if (null == $doggyBagException) {
            throw $this->createNotFoundException('Excepción Doggy Bag no encontrada');
        }

        return $doggyBagException;
    }
}
