<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Update;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\PriorityAssessment;
use App\FoodLoss\Domain\Model\CriticalPoint\SupplyChainStage;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class UpdateCriticalPointRequest implements Command
{
    /**
     * @param ServiceCategoryId[] $serviceCategoryIds
     */
    public function __construct(
        public CriticalPointId $criticalPointId,
        public string $name = '',
        public ?PropertyId $propertyId = null,
        public SupplyChainStage $supplyChainStage = SupplyChainStage::Receiving,
        public array $serviceCategoryIds = [],
        public PriorityAssessment $prevention = new PriorityAssessment(Priority::Prevention),
        public PriorityAssessment $humanDonation = new PriorityAssessment(Priority::HumanDonation),
        public PriorityAssessment $animalFeed = new PriorityAssessment(Priority::AnimalFeed),
        public PriorityAssessment $transformation = new PriorityAssessment(Priority::Transformation),
        public PriorityAssessment $composting = new PriorityAssessment(Priority::Composting),
        public ?AccountId $accountId = null,
    ) {}
}
