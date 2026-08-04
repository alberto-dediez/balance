<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Project;

use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;

trait GetProjectFromIdTrait
{
    private function getProjectFromId(string $projectId, ProjectRepository $repository): Project
    {
        $project = $repository->find(new ProjectId($projectId));

        if (null == $project) {
            throw $this->createNotFoundException('Proyecto no encontrado');
        }

        return $project;
    }
}
