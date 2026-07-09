<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Provider\Register;

use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Domain\Model\Provider\Contact;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Address;

class RegisterProviderService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProviderRepository $providerRepository,
        private ProviderAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterProviderRequest $request, User $user): RegisterProviderResponse
    {
        $id = ProviderId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Provider(
            id: $id,
            accountId: $request->accountId,
        ), SecurityAction::Register, $user);

        $address = new Address(
            street: $request->street ?: null,
            postalCode: $request->postalCode ?: null,
            city: $request->city ?: null,
            country: $request->country ?: null,
        );

        $contact = Contact::fromEmail(
            email: $request->email,
            preferredLocale: $request->preferredLocale,
            name: $request->contactName,
        );

        $provider = new Provider($id);
        $provider->register(
            name: $request->name,
            vatNumber: $request->vatNumber,
            address: $address,
            contact: $contact,
            accountId: $request->accountId,
        );

        $this->providerRepository->add($provider);

        return new RegisterProviderResponse(
            providerId: $provider->id(),
            name: $provider->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterProviderRequest;
    }
}
