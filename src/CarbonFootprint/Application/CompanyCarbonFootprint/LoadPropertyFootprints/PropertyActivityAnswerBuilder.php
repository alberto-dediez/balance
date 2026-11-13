<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\TransportAnswer;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivity;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;

final class PropertyActivityAnswerBuilder
{
    public function __construct(
        private ActivityRepository $activityRepository,
    ) {}

    public function build(PropertyCarbonFootprint $propertyCarbonFootprint, Scope $scope): PropertyActivityAnswers
    {
        $sectionAnswers = [];
        $emissions = new Emissions();
        $count = 0;

        foreach ($scope->sections() as $section) {
            $ids = $propertyCarbonFootprint->activityIds(new ActivityPlacement($scope, $section->source));

            $answers = [];
            foreach ($this->activityRepository->findByIds($ids) as $activity) {
                $emissions = $emissions->add($activity->emissions());
                $answers[] = $this->answer($activity, $scope);
            }

            if ([] === $answers) {
                continue;
            }

            $count += count($answers);
            $sectionAnswers[] = new SectionAnswer($section, $answers);
        }

        return new PropertyActivityAnswers($sectionAnswers, $emissions, $count);
    }

    private function answer(Activity $activity, Scope $scope): ActivityAnswer
    {
        if ($activity instanceof CombustibleActivity) {
            return new CombustibleAnswer($activity->combustibleId(), $activity->quantity(), source: $activity->source());
        }

        if ($activity instanceof TransportActivity) {
            $weight = $activity->weight();
            $distanceKm = $activity->distanceKm();

            if (null !== $weight && null !== $distanceKm) {
                return TransportAnswer::fromWeightAndDistance($activity->transportId(), $weight, $distanceKm, source: $activity->source());
            }

            return new TransportAnswer($activity->transportId(), $activity->quantity(), source: $activity->source());
        }

        if ($activity instanceof RefrigerantGasActivity) {
            return new RefrigerantGasAnswer($activity->refrigerantGasId(), $activity->quantity(), source: $activity->source());
        }

        if ($activity instanceof ElectricityActivity) {
            return new ElectricityAnswer($activity->electricityMarketerId(), $activity->gdOFactorId(), $activity->quantity(), source: $activity->source());
        }

        throw new \LogicException(sprintf('Unexpected activity type "%s" in %s', $activity::class, $scope->key()));
    }
}
