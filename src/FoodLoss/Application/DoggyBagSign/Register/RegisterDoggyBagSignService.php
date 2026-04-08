<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagSign\Register;

use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignAuthorizer;
use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignUploader;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSign;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterDoggyBagSignService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DoggyBagSignRepository $doggyBagSignRepository,
        private DoggyBagSignAuthorizer $authorizer,
        private DoggyBagSignUploader $fileUploader,
    ) {}

    public function __invoke(RegisterDoggyBagSignRequest $request, User $user): RegisterDoggyBagSignResponse
    {
        $id = DoggyBagSignId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new DoggyBagSign(id: $id, accountId: $request->accountId),
            SecurityAction::Register,
            $user,
        );

        assert($request->accountId instanceof AccountId);

        $path = $this->fileUploader->upload($request->temporaryFilePath, $request->fileExtension, $request->accountId);

        $doggyBagSign = new DoggyBagSign($id);
        $doggyBagSign->register(
            name: $request->name,
            location: $request->location,
            path: $path,
            propertyId: $request->propertyId,
            accountId: $request->accountId,
        );

        $this->doggyBagSignRepository->add($doggyBagSign);

        return new RegisterDoggyBagSignResponse(
            doggyBagSignId: $doggyBagSign->id(),
            name: $doggyBagSign->name(),
            path: $doggyBagSign->path(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterDoggyBagSignRequest;
    }
}
