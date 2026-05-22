<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Update;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\AnimalFeedAssessment;
use App\FoodLoss\Domain\Model\Surplus\CompostingAssessment;
use App\FoodLoss\Domain\Model\Surplus\EliminationAssessment;
use App\FoodLoss\Domain\Model\Surplus\FoodType;
use App\FoodLoss\Domain\Model\Surplus\HumanDonationAssessment;
use App\FoodLoss\Domain\Model\Surplus\PreventionAssessment;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\TransformationAssessment;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Shared\Application\CommandBus\Command;

class UpdateSurplusRequest implements Command
{
    public function __construct(
        public SurplusId $surplusId,
        public \DateTimeImmutable $date,
        public ?ServiceCategoryId $serviceCategoryId,
        public ?CriticalPointId $criticalPointId,
        public ?SurplusCauseId $surplusCauseId,
        public FoodType $foodType,
        public float $quantity,
        public DeliveryLogUnit $unit,
        public PreventionAssessment $preventionAssessment = new PreventionAssessment(),
        public TransformationAssessment $transformationAssessment = new TransformationAssessment(),
        public HumanDonationAssessment $humanDonationAssessment = new HumanDonationAssessment(),
        public AnimalFeedAssessment $animalFeedAssessment = new AnimalFeedAssessment(),
        public CompostingAssessment $compostingAssessment = new CompostingAssessment(),
        public EliminationAssessment $eliminationAssessment = new EliminationAssessment(),
    ) {}
}
