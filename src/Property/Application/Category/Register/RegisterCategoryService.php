<?php

namespace App\Property\Application\Category\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Application\Category\CategoryAuthorizer;
use App\Property\Domain\Model\Category\CategoryRepository;

class RegisterCategoryService
{
    public function __construct(private CategoryRepository $categoryRepository, private CategoryAuthorizer $authorizer)
    {
    }

    public function __invoke(RegisterCategoryRequest $request, User $user): RegisterCategoryResponse
    {
        $id = CategoryId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Category(
            id: CategoryId::generate()
        ), Action::Register, $user);

        $category = new Category($id);
        $category->register($request->name, $request->externalId);

        $this->categoryRepository->add($category);

        return new RegisterCategoryResponse($category->id(), $category->name());
    }
}