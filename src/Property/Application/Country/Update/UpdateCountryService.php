<?php

declare(strict_types=1);

namespace App\Property\Application\Country\Update;

use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateCountryService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private CountryRepository $repository, private CountryAuthorizer $authorizer) {}

    public function __invoke(UpdateCountryRequest $request, User $user): UpdateCountryResponse
    {
        $country = $this->repository->find($request->countryId);

        if (null == $country) {
            throw new \InvalidArgumentException('Country not found');
        }

        $this->authorizer->denyUnlessCanDoThat($country, Action::Edit, $user);

        $country->update(
            $request->name,
            $request->iso,
            $request->externalId,
            $request->vatRate,
            $request->europeanUnion,
        );

        $this->repository->add($country);

        return new UpdateCountryResponse(
            countryId: $country->id(),
            name: $country->name(),
            iso: $country->iso(),
            vatRate: $country->vatRate(),
            europeanUnion: $country->isEuropeanUnion(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateCountryRequest;
    }
}
