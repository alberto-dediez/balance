<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Assess\Transformation;

use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\TransformationChannel;
use App\FoodLoss\Domain\Model\Surplus\TransformationProduct;
use App\Shared\Application\CommandBus\Command;

class AssessTransformationRequest implements Command
{
    public function __construct(
        public SurplusId $surplusId,
        public bool $canBeTransformed,
        public ?ReasonId $reasonId,
        public ?TransformationProduct $product = null,
        public ?TransformationChannel $channel = null,
    ) {}
}
