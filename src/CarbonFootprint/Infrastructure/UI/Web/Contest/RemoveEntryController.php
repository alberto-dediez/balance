<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Application\ContestEntry\Remove\RemoveContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\Remove\RemoveContestEntryService;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/contests/{contestId}/entries/{entryId}/remove', name: 'contests_entries_remove', methods: ['GET'])]
#[CsrfProtected('remove')]
class RemoveEntryController extends AbstractController
{
    public function __invoke(
        string $contestId,
        string $entryId,
        LoggedUser $loggedUser,
        RemoveContestEntryService $removeContestEntry,
    ): Response {
        try {
            $removeContestEntry(new RemoveContestEntryRequest(new ContestId($contestId), new ContestEntryId($entryId)), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Inscripción eliminada con éxito'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (\RuntimeException) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la inscripción'));
        }

        return $this->redirectToRoute('contests_entries', ['contestId' => $contestId]);
    }
}
