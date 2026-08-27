<?php

declare(strict_types=1);

namespace App\Property\Application\Country\Register;

use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterCountryService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private CountryRepository $countryRepository, private CountryAuthorizer $authorizer) {}

    public function __invoke(RegisterCountryRequest $request, User $user): RegisterCountryResponse
    {
        $id = CountryId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Country(
            id: CountryId::generate(),
            iso: $request->iso,
        ), Action::Register, $user);

        $country = new Country($id);
        $country->register(
            $request->name,
            $request->iso,
            $request->externalId,
            $request->vatRate,
            $request->europeanUnion,
        );

        $this->countryRepository->add($country);

        return new RegisterCountryResponse(
            countryId: $country->id(),
            name: $country->name(),
            iso: $country->iso(),
            vatRate: $country->vatRate(),
            europeanUnion: $country->isEuropeanUnion(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCountryRequest;
    }
}
