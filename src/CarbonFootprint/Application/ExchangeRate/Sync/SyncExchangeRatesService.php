<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ExchangeRate\Sync;

use App\CarbonFootprint\Application\ExchangeRate\ExchangeRateAuthorizer;
use App\CarbonFootprint\Application\ExchangeRate\Register\RegisterExchangeRateRequest;
use App\CarbonFootprint\Application\ExchangeRate\Register\RegisterExchangeRateService;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateGateway;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepository;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateSourceUnavailableException;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateSyncFailed;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Event\DomainEventPublisher;
use Psr\Log\LoggerInterface;

final class SyncExchangeRatesService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ExchangeRateGateway $gateway,
        private ExchangeRateRepository $repository,
        private RegisterExchangeRateService $registerExchangeRate,
        private ExchangeRateAuthorizer $authorizer,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(SyncExchangeRatesRequest $request, User $user): SyncExchangeRatesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new ExchangeRate(id: ExchangeRateId::generate()), SecurityAction::Admin, $user);

        try {
            $feed = $this->gateway->fetch();
        } catch (ExchangeRateSourceUnavailableException $e) {
            $this->logger->warning('ECB exchange rates unavailable', ['reason' => $e->getMessage()]);

            DomainEventPublisher::instance()->publish(new ExchangeRateSyncFailed(reason: $e->getMessage(), attemptedAt: $request->now));

            return new SyncExchangeRatesResponse(failed: true, failure: $e->getMessage());
        }

        $registered = 0;
        $updated = 0;
        $unchanged = 0;
        $unknownCurrencies = [];

        foreach ($feed->entries as $entry) {
            $unit = Unit::tryFrom($entry->currency);

            if (null === $unit || !$unit->isCurrency() || Unit::Euro === $unit) {
                $unknownCurrencies[] = $entry->currency;

                continue;
            }

            if (!is_numeric($entry->rate) || (float) $entry->rate <= 0) {
                $this->logger->warning('Skipping invalid ECB exchange rate', ['currency' => $entry->currency, 'rate' => $entry->rate]);

                continue;
            }

            $exchangeRate = $this->repository->findByCurrency($unit);

            if (null === $exchangeRate) {
                $this->registerExchangeRate->__invoke(new RegisterExchangeRateRequest($unit, $feed->date, $entry->rate, $request->now), $user);
                ++$registered;

                continue;
            }

            if ($exchangeRate->refresh($feed->date, $entry->rate, $request->now)) {
                $this->repository->add($exchangeRate);
                ++$updated;

                continue;
            }

            ++$unchanged;
        }

        if ([] !== $unknownCurrencies) {
            $this->logger->info('Ignoring unknown ECB currencies', ['currencies' => $unknownCurrencies]);
        }

        $this->logger->info('ECB exchange rates synchronized', [
            'date' => $feed->date->format('Y-m-d'),
            'registered' => $registered,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'unknownCurrencies' => $unknownCurrencies,
        ]);

        return new SyncExchangeRatesResponse(
            registered: $registered,
            updated: $updated,
            unchanged: $unchanged,
            unknownCurrencies: $unknownCurrencies,
            date: $feed->date,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof SyncExchangeRatesRequest;
    }
}
