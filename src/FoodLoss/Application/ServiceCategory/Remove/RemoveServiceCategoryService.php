<?php

namespace App\FoodLoss\Application\ServiceCategory\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RemoveServiceCategoryService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private ServiceCategoryRepository $repository, private ServiceCategoryAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveServiceCategoryRequest $request, User $user): RemoveServiceCategoryResponse
    {
        $serviceCategory = $this->repository->find($request->serviceCategoryId);

        if (null == $serviceCategory) {
            throw new InvalidArgumentException("Service category not found");
        }

        $this->authorizer->denyUnlessCanDoThat($serviceCategory, Action::Remove, $user);

        $serviceCategory->remove();

        $this->repository->add($serviceCategory);

        return new RemoveServiceCategoryResponse(
            serviceCategoryId: $serviceCategory->id()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveServiceCategoryRequest;
    }
}