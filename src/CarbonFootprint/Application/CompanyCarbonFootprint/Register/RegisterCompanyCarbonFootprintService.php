<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\Register;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAlreadyRegisteredException;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterCompanyCarbonFootprintService implements CommandHandler
{
    use CommandInvokeHandlerTrait;
    use RegisterHandlerTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyRepository $companyRepository,
        private CompanyCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterCompanyCarbonFootprintRequest $request, User $user): RegisterCompanyCarbonFootprintResponse
    {
        $id = CompanyCarbonFootprintId::generate();

        $this->authorizer->denyUnlessCanDoThat(new CompanyCarbonFootprint(
            id: $id,
            accountId: $request->accountId,
        ), SecurityAction::Register, $user);

        $companyId = $request->companyId
            ?? throw new \InvalidArgumentException('Company not found in account');

        $accountId = $request->accountId
            ?? throw new \InvalidArgumentException('Company not found in account');

        $emissionFactorGroupId = $request->emissionFactorGroupId
            ?? throw new \InvalidArgumentException('No emission factor group configured');

        $company = $this->companyRepository->find($companyId);

        if (null === $company || $company->isRemoved() || true !== $company->accountId()?->equals($accountId)) {
            throw new \InvalidArgumentException('Company not found in account');
        }

        if (null !== $this->companyCarbonFootprintRepository->findByCompanyIdAndYear($companyId, $request->year)) {
            throw new CompanyCarbonFootprintAlreadyRegisteredException($companyId, $request->year);
        }

        $companyCarbonFootprint = new CompanyCarbonFootprint($id);
        $companyCarbonFootprint->register(
            companyId: $companyId,
            accountId: $accountId,
            emissionFactorGroupId: $emissionFactorGroupId,
            year: $request->year,
        );

        $this->companyCarbonFootprintRepository->add($companyCarbonFootprint);

        return new RegisterCompanyCarbonFootprintResponse($companyCarbonFootprint->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCompanyCarbonFootprintRequest;
    }
}
