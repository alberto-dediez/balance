<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DoggyBagSign;

interface DoggyBagSignRepository
{
    public function add(DoggyBagSign $doggyBagSign): void;

    public function remove(DoggyBagSign $doggyBagSign): void;

    public function find(DoggyBagSignId $doggyBagSignId): ?DoggyBagSign;

    /** @return array<DoggyBagSign> */
    public function findByFilter(DoggyBagSignFilter $filter): array;

    public function totalResultsByFilter(DoggyBagSignFilter $filter): int;
}
