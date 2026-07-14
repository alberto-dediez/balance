<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;

interface EventCarbonFootprintReportStorage
{
    public function store(EventCarbonFootprintReportId $id, ?AccountId $accountId, Locale $locale, string $localPath): string;

    /** @return resource */
    public function read(string $path): mixed;

    public function delete(?string $path): void;
}
