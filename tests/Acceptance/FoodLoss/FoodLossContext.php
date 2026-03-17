<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\FoodLoss;

use Behat\Step\Given;
use Behat\Behat\Context\Context;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryMother;

final class FoodLossContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    public function getActionRepository() : ActionRepository
    {
        return $this->client->getContainer()->get(ActionRepository::class);
    }

    public function getServiceCategoryRepository() : ServiceCategoryRepository
    {
        return $this->client->getContainer()->get(ServiceCategoryRepository::class);
    }

    #[Given('action :description with id :id exists')]
    public function actionWithIdExists($description, $id): void
    {
        $action = ActionMother::fromDescriptionAndId($description, $id);

        $this->getActionRepository()->add($action);
    }

    #[Given('service category :name with id :id exists')]
    public function serviceCategoryWithIdExists($name, $id): void
    {
        $serviceCategory = ServiceCategoryMother::fromNameAndId($name, $id);

        $this->getServiceCategoryRepository()->add($serviceCategory);
    }
}