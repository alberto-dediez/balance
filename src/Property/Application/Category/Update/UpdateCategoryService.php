<?php

namespace App\Property\Application\Category\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Application\Category\CategoryAuthorizer;
use App\Property\Domain\Model\Category\CategoryRepository;

class UpdateCategoryService
{
    public function __construct(private CategoryRepository $repository, private CategoryAuthorizer $authorizer)
    {

    }

    public function __invoke(UpdateCategoryRequest $request, User $user): UpdateCategoryResponse
    {
        $category = $this->repository->find($request->categoryId);

        if (null == $category) {
            throw new InvalidArgumentException("Category not found");
        }

        $this->authorizer->denyUnlessCanDoThat($category, Action::Edit, $user);

        $category->update($request->name, $request->externalId);

        $this->repository->add($category);

        return new UpdateCategoryResponse(
            categoryId: $category->id(),
            name: $category->name()
        );
    }
}