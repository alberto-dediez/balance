<?php

namespace App\Property\Application\Category\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Property\Application\Category\CategoryAuthorizer;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RemoveCategoryService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private CategoryRepository $repository, private CategoryAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveCategoryRequest $request, User $user): RemoveCategoryResponse
    {
        $category = $this->repository->find($request->categoryId);

        if (null == $category) {
            throw new InvalidArgumentException("Category not found");
        }

        $this->authorizer->denyUnlessCanDoThat($category, Action::Remove, $user);

        $category->remove();

        $this->repository->add($category);

        return new RemoveCategoryResponse(
            categoryId: $category->id()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveCategoryRequest;
    }
}