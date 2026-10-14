<?php

declare(strict_types=1);

namespace App\Property\Application\Country\Remove;

use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveCountryService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private CountryRepository $repository, private CountryAuthorizer $authorizer) {}

    public function __invoke(RemoveCountryRequest $request, User $user): RemoveCountryResponse
    {
        $country = $this->repository->find($request->countryId);

        if (null == $country) {
            throw new \InvalidArgumentException('Country not found');
        }

        $this->authorizer->denyUnlessCanDoThat($country, Action::Remove, $user);

        $country->remove();

        $this->repository->add($country);

        return new RemoveCountryResponse(
            countryId: $country->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveCountryRequest;
    }
}
