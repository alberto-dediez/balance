<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Domain\Model\Account\AccountId;

final readonly class KpiSubject
{
    public const string COMPANY_SEGMENT = 'company';
    public const string OTHER_SEGMENT = 'other';

    public function __construct(
        public CarbonFootprintReportSubjectType $type,
        public string $id,
        public ?AccountId $accountId,
        public string $name,
        public string $segmentKey,
    ) {}

    public static function forAccount(CarbonFootprintReportSubjectType $type, ?AccountId $accountId): self
    {
        return new self(
            type: $type,
            id: '',
            accountId: $accountId,
            name: '',
            segmentKey: CarbonFootprintReportSubjectType::Company === $type ? self::COMPANY_SEGMENT : self::OTHER_SEGMENT,
        );
    }

    public function isCompany(): bool
    {
        return CarbonFootprintReportSubjectType::Company === $this->type;
    }
}
