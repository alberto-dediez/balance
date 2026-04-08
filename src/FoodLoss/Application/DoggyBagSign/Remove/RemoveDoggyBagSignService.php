<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagSign\Remove;

use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignAuthorizer;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveDoggyBagSignService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private DoggyBagSignRepository $repository, private DoggyBagSignAuthorizer $authorizer) {}

    public function __invoke(RemoveDoggyBagSignRequest $request, User $user): RemoveDoggyBagSignResponse
    {
        $doggyBagSign = $this->repository->find($request->doggyBagSignId);

        if (null == $doggyBagSign) {
            throw new \InvalidArgumentException('DoggyBagSign not found');
        }

        $this->authorizer->denyUnlessCanDoThat($doggyBagSign, SecurityAction::Remove, $user);

        $doggyBagSign->remove();

        $this->repository->add($doggyBagSign);

        return new RemoveDoggyBagSignResponse(
            doggyBagSignId: $doggyBagSign->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveDoggyBagSignRequest;
    }
}
