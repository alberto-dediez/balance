<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionTokenInvalidException;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/organizational-employee-contribution/{token}/answered', name: 'public_organizational_employee_contribution_answered', methods: ['GET'])]
class AnsweredController extends AbstractController
{
    use BrandedPublicControllerTrait;
    use OrganizationalEmployeeContributionPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $employeeContributionTokenLimiter,
        private RateLimiterFactory $employeeContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(string $token, OrganizationalEmployeeContributionAuthenticator $authenticator, Request $request): Response
    {
        if ($limited = $this->checkOrganizationalEmployeeContributionRateLimit($this->employeeContributionTokenLimiter, $this->employeeContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        try {
            $contribution = $authenticator->authenticate($token)->contribution;
        } catch (OrganizationalEmployeeContributionTokenInvalidException|OrganizationalEmployeeContributionIgnoredException) {
            return $this->organizationalEmployeeContributionInvalidResponse($this->employeeContributionInvalidTokenLimiter, $request);
        }

        $this->brandFor($contribution->accountId());

        return $this->render('@carbon_footprint/public/organizational_employee_contribution/answered.html.twig');
    }
}
