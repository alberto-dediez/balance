<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ExchangeRate\Register;

use App\CarbonFootprint\Application\ExchangeRate\ExchangeRateAuthorizer;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterExchangeRateService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ExchangeRateRepository $repository,
        private ExchangeRateAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterExchangeRateRequest $request, User $user): RegisterExchangeRateResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new ExchangeRate(id: ExchangeRateId::generate()), SecurityAction::Register, $user);

        if (null !== $this->repository->findByCurrency($request->currency)) {
            throw new \InvalidArgumentException('Exchange rate already registered for currency');
        }

        $exchangeRate = new ExchangeRate(id: $request->id ?? ExchangeRateId::generate());
        $exchangeRate->register($request->currency, $request->date, $request->rate, $request->fetchedAt);

        $this->repository->add($exchangeRate);

        return new RegisterExchangeRateResponse(exchangeRate: $exchangeRate);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterExchangeRateRequest;
    }
}
