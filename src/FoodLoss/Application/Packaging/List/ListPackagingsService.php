<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Packaging\List;

use App\FoodLoss\Application\Packaging\PackagingAuthorizer;
use App\FoodLoss\Domain\Model\Packaging\Packaging;
use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListPackagingsService
{
    public function __construct(private PackagingRepository $repository, private PackagingAuthorizer $authorizer) {}

    public function __invoke(ListPackagingsRequest $request, User $user): ListPackagingsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Packaging(id: PackagingId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $packagings = $this->repository->findByFilter($request);
        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListPackagingsResponse($packagings, $totalResults);
    }
}
