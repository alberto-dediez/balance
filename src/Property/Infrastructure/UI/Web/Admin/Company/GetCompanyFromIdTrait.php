<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Company;

use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;

trait GetCompanyFromIdTrait
{
    private function getCompanyFromId(string $companyId, CompanyRepository $repository): Company
    {
        $company = $repository->find(new CompanyId($companyId));

        if (null === $company) {
            throw $this->createNotFoundException('Empresa no encontrada');
        }

        return $company;
    }
}
