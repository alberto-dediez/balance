<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

class TwoFactorNotPendingException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('There is no pending two factor verification');
    }
}
