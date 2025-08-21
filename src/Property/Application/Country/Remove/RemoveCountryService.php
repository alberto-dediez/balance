<?php

namespace App\Property\Application\Country\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RemoveCountryService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private CountryRepository $repository, private CountryAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveCountryRequest $request, User $user): RemoveCountryResponse
    {
        $country = $this->repository->find($request->countryId);

        if (null == $country) {
            throw new InvalidArgumentException("Country not found");
        }

        $this->authorizer->denyUnlessCanDoThat($country, Action::Remove, $user);

        $country->remove();

        $this->repository->add($country);

        return new RemoveCountryResponse(
            countryId: $country->id()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveCountryRequest;
    }
}