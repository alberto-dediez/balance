<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Validator;

use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class UniqueEmissionFactorYears extends Constraint
{
    public string $message;

    public string $yearPath = 'year';

    /** @param array<string>|null $groups */
    public function __construct(
        ?string $message = null,
        ?string $yearPath = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = (string) new TranslatableMessage('Ya existe un factor de emisión para el año {{ year }}', [], 'validators');

        $this->message = $message ?? $this->message;
        $this->yearPath = $yearPath ?? $this->yearPath;
    }
}
