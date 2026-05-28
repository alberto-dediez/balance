<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

enum SignatureRequestStatus: string
{
    case Pending = 'Pending';
    case View = 'View';
    case Signed = 'Signed';
    case Expired = 'Expired';
}
