<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen\ReopenCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen\ReopenCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\EventCarbonFootprint\Reopen\ReopenEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Reopen\ReopenEventCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen\ReopenPropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen\ReopenPropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Domain\Model\User\User;

final class CarbonFootprintReportSubjects
{
    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private ReopenEventCarbonFootprintService $reopenEventCarbonFootprint,
        private ReopenPropertyCarbonFootprintService $reopenPropertyCarbonFootprint,
        private ReopenCompanyCarbonFootprintService $reopenCompanyCarbonFootprint,
    ) {}

    public function find(CarbonFootprintReportSubjectType $type, string $carbonFootprintId): ?CarbonFootprint
    {
        return match ($type) {
            CarbonFootprintReportSubjectType::Event => $this->eventCarbonFootprintRepository->find(new EventCarbonFootprintId($carbonFootprintId)),
            CarbonFootprintReportSubjectType::Property => $this->propertyCarbonFootprintRepository->find(new PropertyCarbonFootprintId($carbonFootprintId)),
            CarbonFootprintReportSubjectType::Company => $this->companyCarbonFootprintRepository->find(new CompanyCarbonFootprintId($carbonFootprintId)),
        };
    }

    public function reopen(CarbonFootprintReportSubjectType $type, string $carbonFootprintId, User $user): void
    {
        match ($type) {
            CarbonFootprintReportSubjectType::Event => ($this->reopenEventCarbonFootprint)(new ReopenEventCarbonFootprintRequest(new EventCarbonFootprintId($carbonFootprintId)), $user),
            CarbonFootprintReportSubjectType::Property => ($this->reopenPropertyCarbonFootprint)(new ReopenPropertyCarbonFootprintRequest(new PropertyCarbonFootprintId($carbonFootprintId)), $user),
            CarbonFootprintReportSubjectType::Company => ($this->reopenCompanyCarbonFootprint)(new ReopenCompanyCarbonFootprintRequest(new CompanyCarbonFootprintId($carbonFootprintId)), $user),
        };
    }
}
