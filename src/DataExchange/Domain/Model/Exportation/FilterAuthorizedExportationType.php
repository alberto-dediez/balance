<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

use App\Security\Domain\Model\User\User;

interface FilterAuthorizedExportationType extends ExportationType
{
    public function isAuthorizedForFilter(object $filter, User $user): bool;
}
