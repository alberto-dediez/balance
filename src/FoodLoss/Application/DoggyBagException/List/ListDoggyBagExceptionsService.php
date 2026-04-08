<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagException\List;

use App\FoodLoss\Application\DoggyBagException\DoggyBagExceptionAuthorizer;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagException;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListDoggyBagExceptionsService
{
    public function __construct(private DoggyBagExceptionRepository $repository, private DoggyBagExceptionAuthorizer $authorizer) {}

    public function __invoke(ListDoggyBagExceptionsRequest $request, User $user): ListDoggyBagExceptionsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new DoggyBagException(id: DoggyBagExceptionId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $doggyBagExceptions = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListDoggyBagExceptionsResponse($doggyBagExceptions, $totalResults);
    }
}
