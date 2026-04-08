<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagException\Remove;

use App\FoodLoss\Application\DoggyBagException\DoggyBagExceptionAuthorizer;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveDoggyBagExceptionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private DoggyBagExceptionRepository $repository, private DoggyBagExceptionAuthorizer $authorizer) {}

    public function __invoke(RemoveDoggyBagExceptionRequest $request, User $user): RemoveDoggyBagExceptionResponse
    {
        $doggyBagException = $this->repository->find($request->doggyBagExceptionId);

        if (null == $doggyBagException) {
            throw new \InvalidArgumentException('Doggy Bag Exception not found');
        }

        $this->authorizer->denyUnlessCanDoThat($doggyBagException, SecurityAction::Remove, $user);

        $doggyBagException->remove();

        $this->repository->add($doggyBagException);

        return new RemoveDoggyBagExceptionResponse(
            doggyBagExceptionId: $doggyBagException->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveDoggyBagExceptionRequest;
    }
}
