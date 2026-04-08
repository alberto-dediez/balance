<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagSign\Update;

use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignAuthorizer;
use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignUploader;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateDoggyBagSignService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DoggyBagSignRepository $doggyBagSignRepository,
        private DoggyBagSignAuthorizer $authorizer,
        private DoggyBagSignUploader $fileUploader,
    ) {}

    public function __invoke(UpdateDoggyBagSignRequest $request, User $user): UpdateDoggyBagSignResponse
    {
        $doggyBagSign = $this->doggyBagSignRepository->find($request->doggyBagSignId);

        if (null == $doggyBagSign) {
            throw new \InvalidArgumentException('DoggyBagSign not found');
        }

        $this->authorizer->denyUnlessCanDoThat($doggyBagSign, SecurityAction::Edit, $user);

        if (null !== $request->temporaryFilePath && null !== $request->fileExtension) {
            assert($doggyBagSign->accountId() instanceof AccountId);
            $path = $this->fileUploader->upload($request->temporaryFilePath, $request->fileExtension, $doggyBagSign->accountId());
        } else {
            $path = $doggyBagSign->path();
        }

        $doggyBagSign->update(
            name: $request->name,
            location: $request->location,
            path: $path,
            propertyId: $request->propertyId,
        );

        $this->doggyBagSignRepository->add($doggyBagSign);

        return new UpdateDoggyBagSignResponse(
            doggyBagSignId: $doggyBagSign->id(),
            name: $doggyBagSign->name(),
            path: $doggyBagSign->path(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateDoggyBagSignRequest;
    }
}
