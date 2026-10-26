<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\User;

use App\Security\Domain\Model\User\Username;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

class UserRequest
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'user@dediez.software',
        )]
        #[Assert\Regex(pattern: Username::PATTERN, message: 'El nombre de usuario no puede contener los caracteres < ni >')]
        public string $username,
        #[OA\Property(
            type: 'string',
            example: '8de87304-f72e-4a62-bca9-313b27d068f3',
        )]
        public string $accountId,
    ) {}
}
