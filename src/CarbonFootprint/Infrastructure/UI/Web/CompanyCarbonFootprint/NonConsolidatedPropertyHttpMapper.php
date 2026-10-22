<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertyAnswer;
use App\Property\Domain\Model\Property\PropertyId;
use Symfony\Component\HttpFoundation\Request;

final class NonConsolidatedPropertyHttpMapper
{
    /** @return list<NonConsolidatedPropertyAnswer> */
    public function map(Request $request): array
    {
        $answers = [];

        foreach ($request->request->all('nonConsolidated') as $propertyId => $row) {
            if (!is_array($row)) {
                continue;
            }

            $answers[] = new NonConsolidatedPropertyAnswer(
                new PropertyId((string) $propertyId),
                $this->manualCo2eq((string) $propertyId, $row['co2eq'] ?? null),
            );
        }

        return $answers;
    }

    private function manualCo2eq(string $propertyId, mixed $value): ?string
    {
        $value = is_scalar($value) ? trim(str_replace(',', '.', (string) $value)) : '';

        if ('' === $value) {
            return null;
        }

        if (!is_numeric($value) || (float) $value < 0) {
            throw new \InvalidArgumentException(sprintf('Invalid emissions for property "%s"', $propertyId));
        }

        return $value;
    }
}
