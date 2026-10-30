<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OperatorContribution;

use App\CarbonFootprint\Application\OperatorContribution\Resend\ResendOperatorContributionNotificationRequest;
use App\CarbonFootprint\Application\OperatorContribution\Resend\ResendOperatorContributionNotificationService;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/operators/{contributionId}/resend', name: 'company_carbon_footprints_operators_resend', methods: ['GET'])]
#[CsrfProtected('resend')]
class ResendController extends AbstractController
{
    use GetOperatorContributionFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        string $contributionId,
        OperatorContributionRepository $contributionRepository,
        ResendOperatorContributionNotificationService $service,
        LoggedUser $loggedUser,
    ): Response {
        $contribution = $this->getOperatorContributionFromId($contributionId, $companyCarbonFootprintId, $contributionRepository);

        try {
            $service(new ResendOperatorContributionNotificationRequest($contribution->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha reenviado la invitación a {email}', ['{email}' => $contribution->contactEmail()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (OperatorContributionClosedException) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));
        } catch (\LogicException) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido reenviar la invitación a {email}', ['{email}' => $contribution->contactEmail()]));
        }

        return $this->redirectToRoute('company_carbon_footprints_scope_3', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
    }
}
