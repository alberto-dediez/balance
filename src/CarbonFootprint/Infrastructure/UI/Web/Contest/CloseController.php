<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Application\Contest\Close\CloseContestRequest;
use App\CarbonFootprint\Application\Contest\Close\CloseContestService;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/contests/{contestId}/close', name: 'contests_close', methods: ['GET'])]
#[CsrfProtected('close')]
class CloseController extends AbstractController
{
    public function __invoke(
        string $contestId,
        LoggedUser $loggedUser,
        CloseContestService $service,
    ): Response {
        try {
            $service(new CloseContestRequest(new ContestId($contestId)), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Concurso cerrado con éxito'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (\RuntimeException) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido cerrar el concurso'));
        }

        return $this->redirectToRoute('contests_edit', ['contestId' => $contestId]);
    }
}
