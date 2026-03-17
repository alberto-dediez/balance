<?php

namespace App\FoodLoss\Application\ServiceCategory\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;

class ListServiceCategoriesService
{
    public function __construct(private ServiceCategoryRepository $repository, private ServiceCategoryAuthorizer $authorizer)
    {

    }

    public function __invoke(ListServiceCategoriesRequest $request, User $user): ListServiceCategoriesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new ServiceCategory(id: ServiceCategoryId::generate()), Action::List, $user);

        $serviceCategories = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListServiceCategoriesResponse($serviceCategories, $totalResults);
    }
}