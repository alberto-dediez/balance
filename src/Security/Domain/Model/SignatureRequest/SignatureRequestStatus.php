<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

enum SignatureRequestStatus: string
{
    case Pending = 'Pending';
    case Viewed = 'Viewed';
    case Signed = 'Signed';
    case Expired = 'Expired';

    public function style(): string
    {
        return match ($this) {
            self::Pending  => 'bg-warning',
            self::Viewed   => 'bg-info',
            self::Signed   => 'bg-success',
            self::Expired  => 'bg-danger',
        };
    }
}
