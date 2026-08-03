<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\Project;

use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectStatus;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Property\Domain\Model\Destination\DestinationId;

class ProjectMother
{
    public static function registeredProject(): Project
    {
        return self::fromName('project-name');
    }

    public static function fromName(string $name): Project
    {
        return self::from(
            name: $name,
            id: (string) ProjectId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Project
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function fromNameAndIdAndLocation(string $name, string $id, string $location): Project
    {
        return self::from(
            name: $name,
            id: $id,
            location: new DestinationId($location),
        );
    }

    /** @param array<\App\Library\Domain\Model\Media\MediaId> $gallery */
    public static function from(
        string $name,
        string $id,
        string $description = 'project-description',
        ?DestinationId $location = null,
        array $gallery = [],
        ProjectType $projectType = ProjectType::EmissionCompensation,
        ProjectStatus $projectStatus = ProjectStatus::Pending,
        ?string $mitecoId = null,
    ): Project {
        $project = new Project(
            id: new ProjectId($id),
        );
        $project->register(
            name: $name,
            description: $description,
            location: $location ?? DestinationId::generate(),
            gallery: $gallery,
            projectType: $projectType,
            projectStatus: $projectStatus,
            mitecoId: $mitecoId,
        );

        return $project;
    }

    public static function random(): Project
    {
        return self::from(
            name: 'project-' . rand(100, 999),
            id: (string) ProjectId::generate(),
        );
    }
}
