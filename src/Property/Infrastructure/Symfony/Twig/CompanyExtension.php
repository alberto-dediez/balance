<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Twig;

use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CompanyExtension extends AbstractExtension
{
    /** @var array<string, ?Company> */
    private array $companies = [];

    public function __construct(private readonly CompanyRepository $companyRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('company_name', $this->companyName(...)),
        ];
    }

    public function companyName(?CompanyId $companyId): ?Company
    {
        if (null === $companyId) {
            return null;
        }

        if (!isset($this->companies[(string) $companyId])) {
            $this->companies[(string) $companyId] = $this->companyRepository->find($companyId);
        }

        return $this->companies[(string) $companyId];
    }
}
