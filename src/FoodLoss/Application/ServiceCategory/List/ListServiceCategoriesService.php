<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ServiceCategory\List;

use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ListServiceCategoriesService
{
    public function __construct(private readonly ServiceCategoryRepository $repository, private readonly ServiceCategoryAuthorizer $authorizer) {}

    public function __invoke(ListServiceCategoriesRequest $request, User $user): ListServiceCategoriesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new ServiceCategory(id: ServiceCategoryId::generate()), Action::List, $user);

        $serviceCategories = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListServiceCategoriesResponse($serviceCategories, $totalResults);
    }
}
