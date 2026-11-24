<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Export;

use App\Balance\Application\ProjectContribution\List\ListProjectContributionsRequest;
use App\Security\Domain\Model\Account\AccountId;

class ProjectContributionsExportFilter extends ListProjectContributionsRequest
{
    public ?AccountId $viewerAccountId = null;
}
