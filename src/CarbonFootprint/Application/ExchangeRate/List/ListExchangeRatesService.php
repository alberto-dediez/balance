<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ExchangeRate\List;

use App\CarbonFootprint\Application\ExchangeRate\ExchangeRateAuthorizer;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListExchangeRatesService
{
    public function __construct(
        private ExchangeRateRepository $repository,
        private ExchangeRateAuthorizer $authorizer,
    ) {}

    public function __invoke(ListExchangeRatesRequest $request, User $user): ListExchangeRatesResponse
    {
        $subject = new ExchangeRate(id: ExchangeRateId::generate());

        $this->authorizer->denyUnlessCanDoThat($subject, SecurityAction::List, $user);

        return new ListExchangeRatesResponse(
            exchangeRates: $this->repository->findByFilter($request),
            totalResults: $this->repository->totalResultsByFilter($request),
            canSync: $this->authorizer->canDoThat($subject, SecurityAction::Admin, $user),
        );
    }
}
