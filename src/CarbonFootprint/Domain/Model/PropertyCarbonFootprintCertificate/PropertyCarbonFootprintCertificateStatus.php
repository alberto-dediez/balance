<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

enum PropertyCarbonFootprintCertificateStatus: string
{
    case Pending = 'pending';

    case Generated = 'generated';

    case Failed = 'failed';

    case Revoked = 'revoked';

    public function style(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning text-dark',
            self::Generated => 'bg-success',
            self::Failed => 'bg-danger',
            self::Revoked => 'bg-secondary',
        };
    }

    public function isPending(): bool
    {
        return self::Pending === $this;
    }

    public function isGenerated(): bool
    {
        return self::Generated === $this;
    }

    public function isFailed(): bool
    {
        return self::Failed === $this;
    }

    public function isRevoked(): bool
    {
        return self::Revoked === $this;
    }
}
