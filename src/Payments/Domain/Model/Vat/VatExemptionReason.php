<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Vat;

enum VatExemptionReason: string
{
    case Standard = 'standard';

    case OssDestination = 'oss_destination';

    case ReverseCharge = 'reverse_charge';

    case OutsideEu = 'outside_eu';
}
