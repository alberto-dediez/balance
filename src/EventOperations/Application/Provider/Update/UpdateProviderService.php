<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Provider\Update;

use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Address;

class UpdateProviderService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProviderRepository $providerRepository,
        private ProviderAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateProviderRequest $request, User $user): UpdateProviderResponse
    {
        $provider = $this->providerRepository->find($request->providerId);

        if (null == $provider) {
            throw new \InvalidArgumentException('Provider not found');
        }

        $this->authorizer->denyUnlessCanDoThat($provider, SecurityAction::Edit, $user);

        $address = new Address(
            street: $request->street ?: null,
            postalCode: $request->postalCode ?: null,
            city: $request->city ?: null,
            country: $request->country ?: null,
        );

        $provider->update(
            name: $request->name,
            vatNumber: $request->vatNumber,
            address: $address,
        );

        $this->providerRepository->add($provider);

        return new UpdateProviderResponse(
            providerId: $provider->id(),
            name: $provider->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateProviderRequest;
    }
}
