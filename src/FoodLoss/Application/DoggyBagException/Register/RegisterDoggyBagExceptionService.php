<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagException\Register;

use App\FoodLoss\Application\DoggyBagException\DoggyBagExceptionAuthorizer;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagException;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterDoggyBagExceptionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DoggyBagExceptionRepository $doggyBagExceptionRepository,
        private DoggyBagExceptionAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterDoggyBagExceptionRequest $request, User $user): RegisterDoggyBagExceptionResponse
    {
        $id = DoggyBagExceptionId::generate();

        assert($request->accountId instanceof AccountId);
        assert(null !== $request->criticalPointId);

        $this->authorizer->denyUnlessCanDoThat(new DoggyBagException(id: $id, accountId: $request->accountId), SecurityAction::List, $user);

        $doggyBagException = new DoggyBagException($id);
        $doggyBagException->register(
            $request->description,
            $request->criticalPointId,
            $request->accountId,
        );

        $this->doggyBagExceptionRepository->add($doggyBagException);

        return new RegisterDoggyBagExceptionResponse(
            doggyBagExceptionId: $doggyBagException->id(),
            description: $doggyBagException->description(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterDoggyBagExceptionRequest;
    }
}
