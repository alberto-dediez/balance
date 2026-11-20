<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContactRequest;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;

class ContactRequestAlreadyExistsException extends \RuntimeException
{
    public function __construct(ProjectContributionOrigin $origin, string $originId)
    {
        parent::__construct(
            sprintf('Ya existe una solicitud de contacto para %s %s.', $origin->value, $originId),
            409,
        );
    }
}
