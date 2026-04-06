<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\FoodLoss;

use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonMother;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationMother;
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

    public function getReasonRepository(): ReasonRepository
    {
        return $this->client->getContainer()->get(ReasonRepository::class);
    }

    public function getRecipientOrganizationRepository(): RecipientOrganizationRepository
    {
        return $this->client->getContainer()->get(RecipientOrganizationRepository::class);
    }

    public function getDeliveryLogRepository(): DeliveryLogRepository
    {
        return $this->client->getContainer()->get(DeliveryLogRepository::class);
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

    #[Given('reason :description with id :id exists')]
    public function reasonWithIdExists(string $description, string $id): void
    {
        $reason = ReasonMother::fromDescriptionAndId($description, $id);

        $this->getReasonRepository()->add($reason);
    }

    #[Given('critical point :name with id :id of account :accountId and property :propertyId exists')]
    public function criticalPointWithIdOfAccountAndPropertyExists(string $name, string $id, string $accountId, string $propertyId): void
    {
        $criticalPoint = CriticalPointMother::from(
            name: $name,
            id: $id,
            accountId: new AccountId($accountId),
            propertyId: new PropertyId($propertyId),
        );

        $this->getCriticalPointRepository()->add($criticalPoint);
    }

    #[Given('recipient organization :name with id :id of account :accountId exists')]
    public function recipientOrganizationWithIdOfAccountExists(string $name, string $id, string $accountId): void
    {
        $recipientOrganization = RecipientOrganizationMother::from(
            name: $name,
            id: $id,
            accountId: $accountId,
        );

        $this->getRecipientOrganizationRepository()->add($recipientOrganization);
    }

    #[Given('delivery log :productType with id :id of account :accountId exists')]
    public function deliveryLogWithIdOfAccountExists(string $productType, string $id, string $accountId): void
    {
        $deliveryLog = DeliveryLogMother::from(
            productType: $productType,
            id: $id,
            accountId: $accountId,
        );

        $this->getDeliveryLogRepository()->add($deliveryLog);
    }
}
