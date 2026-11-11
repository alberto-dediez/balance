<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\ApiToken;

class ApiTokensNotAllowedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('La cuenta no permite tokens de API.', 403);
    }
}
