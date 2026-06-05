<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Alert;

use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\AbnormallyHighParametersType;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\AbnormallyLowParametersType;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\AlertParametersType;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\FixedThresholdParametersType;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\InactiveUserParametersType;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\NoMeasurementsParametersType;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\ThresholdExceededParametersType;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\UnusedContainerParametersType;

enum AlertType: string
{
    case NoMeasurements = 'NoMeasurements';
    case AbnormallyHigh = 'AbnormallyHigh';
    case ThresholdExceeded = 'ThresholdExceeded';
    case FixedThreshold = 'FixedThreshold';
    case AbnormallyLow = 'AbnormallyLow';
    case UnusedContainer = 'UnusedContainer';
    case InactiveUser = 'InactiveUser';

    /** @return class-string<AlertParametersType> */
    public function parametersFormType(): string
    {
        return match ($this) {
            self::NoMeasurements => NoMeasurementsParametersType::class,
            self::AbnormallyHigh => AbnormallyHighParametersType::class,
            self::ThresholdExceeded => ThresholdExceededParametersType::class,
            self::FixedThreshold => FixedThresholdParametersType::class,
            self::AbnormallyLow => AbnormallyLowParametersType::class,
            self::UnusedContainer => UnusedContainerParametersType::class,
            self::InactiveUser => InactiveUserParametersType::class,
        };
    }
}
