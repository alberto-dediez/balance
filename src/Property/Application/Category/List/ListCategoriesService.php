<?php

namespace App\Property\Application\Category\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Application\Category\CategoryAuthorizer;
use App\Property\Domain\Model\Category\CategoryRepository;

class ListCategoriesService
{
    public function __construct(private readonly CategoryRepository $repository, private readonly CategoryAuthorizer $authorizer)
    {

    }

    public function __invoke(ListCategoriesRequest $request, User $user): ListCategoriesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Category(
            id: CategoryId::generate()
        ), Action::List, $user);

        $categories = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListCategoriesResponse($categories, $totalResults);
    }
}