<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Application\Contest\Draw\DrawContestWinnersRequest;
use App\CarbonFootprint\Application\Contest\Draw\DrawContestWinnersService;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/contests/{contestId}/draw', name: 'contests_draw', methods: ['GET'])]
#[CsrfProtected('draw')]
class DrawController extends AbstractController
{
    public function __invoke(
        string $contestId,
        LoggedUser $loggedUser,
        DrawContestWinnersService $service,
    ): Response {
        try {
            $service(new DrawContestWinnersRequest(new ContestId($contestId)), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Sorteo realizado con éxito'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (\RuntimeException) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido realizar el sorteo'));
        }

        return $this->redirectToRoute('contests_entries', ['contestId' => $contestId]);
    }
}
