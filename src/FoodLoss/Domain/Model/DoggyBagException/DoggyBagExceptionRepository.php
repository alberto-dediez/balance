<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DoggyBagException;

interface DoggyBagExceptionRepository
{
    public function add(DoggyBagException $doggyBagException): void;

    public function remove(DoggyBagException $doggyBagException): void;

    public function find(DoggyBagExceptionId $doggyBagExceptionId): ?DoggyBagException;

    /** @return array<DoggyBagException> */
    public function findByFilter(DoggyBagExceptionFilter $filter): array;

    public function totalResultsByFilter(DoggyBagExceptionFilter $filter): int;
}
