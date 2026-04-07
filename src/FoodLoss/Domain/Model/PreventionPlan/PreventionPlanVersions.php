<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use Doctrine\Common\Collections\ArrayCollection;

/** @extends ArrayCollection<int, PreventionPlanVersion> */
final class PreventionPlanVersions extends ArrayCollection {}
