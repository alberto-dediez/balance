<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagSign;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSign;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;

trait GetDoggyBagSignFromIdTrait
{
    private function getDoggyBagSignFromId(string $doggyBagSignId, DoggyBagSignRepository $repository): DoggyBagSign
    {
        $doggyBagSign = $repository->find(new DoggyBagSignId($doggyBagSignId));

        if (null == $doggyBagSign) {
            throw $this->createNotFoundException('Cartel Doggy Bag no encontrado');
        }

        return $doggyBagSign;
    }
}
