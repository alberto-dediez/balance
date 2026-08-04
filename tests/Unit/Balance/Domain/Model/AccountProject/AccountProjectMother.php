<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\AccountProject;

use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;

class AccountProjectMother
{
    public static function selectedAccountProject(): AccountProject
    {
        return self::from(AccountId::generate(), ProjectId::generate());
    }

    public static function from(AccountId $accountId, ProjectId $projectId, ?string $id = null): AccountProject
    {
        $accountProject = new AccountProject(
            id: null !== $id ? new AccountProjectId($id) : AccountProjectId::generate(),
        );

        $accountProject->select($accountId, $projectId);

        return $accountProject;
    }

    public static function random(): AccountProject
    {
        return self::from(AccountId::generate(), ProjectId::generate());
    }
}
