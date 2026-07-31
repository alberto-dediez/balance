<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\EmployeeContribution;

use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionTokenInvalidException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/employee-contribution/{token}/answered', name: 'public_carbon_footprint_employee_contribution_answered', methods: ['GET'])]
class AnsweredController extends AbstractController
{
    use EmployeeContributionPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $employeeContributionTokenLimiter,
        private RateLimiterFactory $employeeContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(string $token, EmployeeContributionAuthenticator $authenticator, Request $request): Response
    {
        if ($limited = $this->checkEmployeeContributionRateLimit($this->employeeContributionTokenLimiter, $this->employeeContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        try {
            $authenticator->authenticate($token);
        } catch (EmployeeContributionTokenInvalidException|EmployeeContributionIgnoredException) {
            return $this->employeeContributionInvalidResponse($this->employeeContributionInvalidTokenLimiter, $request);
        }

        return $this->render('@carbon_footprint/public/employee_contribution/answered.html.twig');
    }
}
