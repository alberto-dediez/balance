<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\FoodLoss;

use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class FoodLossContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    public function getActionRepository(): ActionRepository
    {
        return $this->client->getContainer()->get(ActionRepository::class);
    }

    public function getServiceCategoryRepository(): ServiceCategoryRepository
    {
        return $this->client->getContainer()->get(ServiceCategoryRepository::class);
    }

    public function getCriticalPointRepository(): CriticalPointRepository
    {
        return $this->client->getContainer()->get(CriticalPointRepository::class);
    }

    #[Given('action :description with id :id exists')]
    public function actionWithIdExists(string $description, string $id): void
    {
        $action = ActionMother::fromDescriptionAndId($description, $id);

        $this->getActionRepository()->add($action);
    }

    #[Given('service category :name with id :id exists')]
    public function serviceCategoryWithIdExists(string $name, string $id): void
    {
        $serviceCategory = ServiceCategoryMother::fromNameAndId($name, $id);

        $this->getServiceCategoryRepository()->add($serviceCategory);
    }

    #[Given('critical point :name with id :id exists')]
    public function criticalPointWithIdExists(string $name, string $id): void
    {
        $criticalPoint = CriticalPointMother::fromNameAndId($name, $id);

        $this->getCriticalPointRepository()->add($criticalPoint);
    }

    #[Given('critical point :name with id :id of account :accountId and property :propertyId exists')]
    public function criticalPointWithIdOfAccountAndPropertyExists(string $name, string $id, string $accountId, string $propertyId): void
    {
        $criticalPoint = CriticalPointMother::from(
            name: $name,
            id: $id,
            accountId: new \App\Security\Domain\Model\Account\AccountId($accountId),
            propertyId: new \App\Property\Domain\Model\Property\PropertyId($propertyId),
        );

        $this->getCriticalPointRepository()->add($criticalPoint);
    }
}
