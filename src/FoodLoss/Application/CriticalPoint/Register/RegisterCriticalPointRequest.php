<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Register;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\PriorityAssessment;
use App\FoodLoss\Domain\Model\CriticalPoint\SupplyChainStage;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterCriticalPointRequest implements Command
{
    public PriorityAssessment $prevention;
    public PriorityAssessment $humanDonation;
    public PriorityAssessment $animalFeed;
    public PriorityAssessment $transformation;
    public PriorityAssessment $composting;

    /**
     * @param ServiceCategoryId[] $serviceCategoryIds
     * @param SurplusCauseId[]    $surplusCauseIds
     */
    public function __construct(
        public string $name = '',
        public ?PropertyId $propertyId = null,
        public SupplyChainStage $supplyChainStage = SupplyChainStage::Receiving,
        public array $serviceCategoryIds = [],
        public array $surplusCauseIds = [],
        public ?AccountId $accountId = null,
    ) {
        $this->prevention = new PriorityAssessment(Priority::Prevention);
        $this->humanDonation = new PriorityAssessment(Priority::HumanDonation);
        $this->animalFeed = new PriorityAssessment(Priority::AnimalFeed);
        $this->transformation = new PriorityAssessment(Priority::Transformation);
        $this->composting = new PriorityAssessment(Priority::Composting);
    }
}
