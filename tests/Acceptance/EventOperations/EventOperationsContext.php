<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\EventOperations;

use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final readonly class EventOperationsContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    private function getProviderRepository(): ProviderRepository
    {
        return $this->client->getContainer()->get(ProviderRepository::class);
    }

    #[Given('provider :name with id :id exists')]
    public function providerWithIdExists(string $name, string $id): void
    {
        $provider = ProviderMother::fromNameAndId($name, $id);

        $this->getProviderRepository()->add($provider);
    }

    #[Given('provider :name with id :id of account :account exists')]
    public function providerWithIdOfAccountExists(string $name, string $id, string $account): void
    {
        $provider = ProviderMother::fromNameAndAccountAndId($name, $account, $id);

        $this->getProviderRepository()->add($provider);
    }
}
