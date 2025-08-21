<?php

namespace App\Property\Application\Category\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryId;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Property\Application\Category\CategoryAuthorizer;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RegisterCategoryService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

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

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCategoryRequest;
    }
}