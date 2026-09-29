<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Twig;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MitigationExtension extends AbstractExtension
{
    public function __construct(
        private readonly MitigationMeasureRepository $mitigationMeasureRepository,
        private readonly MitigationMeasureCategoryRepository $mitigationMeasureCategoryRepository,
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('mitigation_measure_name', $this->mitigationMeasureName(...)),
            new TwigFilter('mitigation_measure_category_name', $this->mitigationMeasureCategoryName(...)),
        ];
    }

    public function mitigationMeasureName(MitigationMeasureId|string|null $mitigationMeasureId): string
    {
        if (null === $mitigationMeasureId || '' === (string) $mitigationMeasureId) {
            return '---';
        }

        $id = $mitigationMeasureId instanceof MitigationMeasureId ? $mitigationMeasureId : new MitigationMeasureId($mitigationMeasureId);
        $mitigationMeasure = $this->mitigationMeasureRepository->find($id);

        return $mitigationMeasure instanceof MitigationMeasure ? $mitigationMeasure->name() : '---';
    }

    public function mitigationMeasureCategoryName(MitigationMeasureCategoryId|string|null $mitigationMeasureCategoryId): string
    {
        if (null === $mitigationMeasureCategoryId || '' === (string) $mitigationMeasureCategoryId) {
            return '---';
        }

        $id = $mitigationMeasureCategoryId instanceof MitigationMeasureCategoryId ? $mitigationMeasureCategoryId : new MitigationMeasureCategoryId($mitigationMeasureCategoryId);
        $mitigationMeasureCategory = $this->mitigationMeasureCategoryRepository->find($id);

        return $mitigationMeasureCategory instanceof MitigationMeasureCategory ? $mitigationMeasureCategory->name() : '---';
    }
}
