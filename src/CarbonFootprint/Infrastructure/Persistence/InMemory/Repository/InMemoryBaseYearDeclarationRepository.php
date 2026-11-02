<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclaration;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationId;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryBaseYearDeclarationRepository implements BaseYearDeclarationRepository
{
    /** @param array<string, BaseYearDeclaration> $declarations */
    public function __construct(
        private array $declarations = [],
    ) {}

    public function add(BaseYearDeclaration $declaration): void
    {
        $this->declarations[(string) $declaration->id()] = $declaration;
    }

    public function remove(BaseYearDeclaration $declaration): void
    {
        unset($this->declarations[(string) $declaration->id()]);
    }

    public function find(BaseYearDeclarationId $id): ?BaseYearDeclaration
    {
        return $this->declarations[(string) $id] ?? null;
    }

    public function findBySubject(CarbonFootprintReportSubjectType $subjectType, string $subjectId): ?BaseYearDeclaration
    {
        foreach ($this->declarations as $declaration) {
            if ($declaration->subjectType() === $subjectType && $declaration->subjectId() === $subjectId) {
                return $declaration;
            }
        }

        return null;
    }
}
