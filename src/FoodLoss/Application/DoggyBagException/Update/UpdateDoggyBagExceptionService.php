<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagException\Update;

use App\FoodLoss\Application\DoggyBagException\DoggyBagExceptionAuthorizer;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateDoggyBagExceptionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DoggyBagExceptionRepository $doggyBagExceptionRepository,
        private DoggyBagExceptionAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateDoggyBagExceptionRequest $request, User $user): UpdateDoggyBagExceptionResponse
    {
        $doggyBagException = $this->doggyBagExceptionRepository->find($request->doggyBagExceptionId);

        if (null == $doggyBagException) {
            throw new \InvalidArgumentException('Doggy Bag Exception not found');
        }

        $this->authorizer->denyUnlessCanDoThat($doggyBagException, SecurityAction::Edit, $user);

        assert(null !== $request->criticalPointId);

        $doggyBagException->update(
            description: $request->description,
            criticalPointId: $request->criticalPointId,
        );

        $this->doggyBagExceptionRepository->add($doggyBagException);

        return new UpdateDoggyBagExceptionResponse(
            doggyBagExceptionId: $doggyBagException->id(),
            description: $doggyBagException->description(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateDoggyBagExceptionRequest;
    }
}
