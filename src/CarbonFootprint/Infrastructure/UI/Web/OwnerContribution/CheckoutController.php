<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution;

use App\Balance\Application\ProjectContribution\Options\ContributionOption;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsRequest;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\Balance\Application\ProjectContribution\OwnerContributorDefaults;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionCheckoutHandler;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionContext;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compensate/{origin}/{originId}/{projectId}', name: 'owner_contribution_checkout', methods: ['GET', 'POST'])]
class CheckoutController extends AbstractController
{
    use OwnerContributionTrait;

    public function __invoke(
        string $origin,
        string $originId,
        string $projectId,
        OwnerContributionContextResolver $contextResolver,
        ListContributionOptionsService $listContributionOptions,
        OwnerContributionCheckoutHandler $ownerCheckoutHandler,
        ContributionCheckoutHandler $checkoutHandler,
        OwnerContributorDefaults $ownerContributorDefaults,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = $loggedUser();
        $context = $this->resolveContext($origin, $originId, $contextResolver, $user);
        $routes = $this->contributionRoutes($context);

        if ($request->isMethod('POST') && !$this->isCsrfTokenValid($routes->csrfTokenId, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $option = $listContributionOptions(new ListContributionOptionsRequest(
            accountId: $context->accountId,
            co2eqKg: $context->suggestedCo2eqKg,
            ownerMode: true,
        ))->find($projectId);

        if (!$option instanceof ContributionOption) {
            throw $this->createNotFoundException('Proyecto no disponible para esta cuenta.');
        }

        $result = $option->funding->isOwnCredit()
            ? $ownerCheckoutHandler->handle($context, $routes, $projectId, $user, $request)
            : $checkoutHandler->handle(
                $this->paidContext($context),
                $routes,
                $projectId,
                $request,
                $this->paidDefaults($context, $ownerContributorDefaults, $user),
            );

        if ($result->invalid) {
            throw $this->createNotFoundException('Proyecto no disponible para esta cuenta.');
        }

        if (null !== $result->redirectUrl) {
            return $this->redirect($result->redirectUrl);
        }

        $parameters = $result->parameters + ['originName' => $context->originName];

        $template = $option->funding->isOwnCredit()
            ? '@carbon_footprint/owner_contribution/%s.html.twig'
            : '@carbon_footprint/project_contribution/%s.html.twig';

        return $this->render(
            sprintf($template, $request->isXmlHttpRequest() ? '_summary' : 'checkout'),
            $parameters,
        );
    }

    private function paidContext(OwnerContributionContext $context): ContributionContext
    {
        return new ContributionContext(
            accountId: $context->accountId,
            co2eqKg: $context->suggestedCo2eqKg ?? 0.0,
            origin: $context->origin,
            originId: $context->originId,
            originName: $context->originName,
            token: '',
        );
    }

    /** @return array<string, mixed> */
    private function paidDefaults(
        OwnerContributionContext $context,
        OwnerContributorDefaults $ownerContributorDefaults,
        User $user,
    ): array {
        $defaults = $ownerContributorDefaults($context->accountId, $user);

        return [
            'mode' => null !== $context->suggestedCo2eqKg ? 'emissions' : 'free',
            'name' => $defaults->name,
            'email' => $defaults->email,
            'country' => $defaults->country ?? '',
            'isCompany' => $defaults->isCompany,
            'companyName' => $defaults->companyName ?? '',
            'vatNumber' => $defaults->vatNumber ?? '',
        ];
    }
}
