<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Twig;

use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ProjectExtension extends AbstractExtension
{
    /**
     * @var array<string, ?Project>
     */
    private array $projects = [];

    public function __construct(private readonly ProjectRepository $projectRepository) {}

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('project_name', $this->projectName(...)),
            new TwigFilter('project_id', $this->projectId(...)),
        ];
    }

    public function projectId(mixed $projectId): ProjectId
    {
        return new ProjectId((string) $projectId);
    }

    public function projectName(ProjectId $projectId): ?Project
    {
        if (!isset($this->projects[(string) $projectId])) {
            $this->projects[(string) $projectId] = $this->projectRepository->find($projectId);
        }

        return $this->projects[(string) $projectId];
    }
}
