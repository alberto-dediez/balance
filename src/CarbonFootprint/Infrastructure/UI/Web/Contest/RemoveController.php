<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Application\Contest\Remove\RemoveContestRequest;
use App\CarbonFootprint\Application\Contest\Remove\RemoveContestService;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/contests/{contestId}/remove', name: 'contests_remove', methods: ['GET'])]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    public function __invoke(
        string $contestId,
        LoggedUser $loggedUser,
        RemoveContestService $service,
    ): Response {
        try {
            $service(new RemoveContestRequest(new ContestId($contestId)), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Concurso eliminado con éxito'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (\RuntimeException) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el concurso'));
        }

        return $this->redirectToRoute('contests');
    }
}
