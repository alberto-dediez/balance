<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Application\Contest\PurgePersonalData\PurgeContestPersonalDataRequest;
use App\CarbonFootprint\Application\Contest\PurgePersonalData\PurgeContestPersonalDataService;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/contests/{contestId}/purge', name: 'contests_purge', methods: ['GET'])]
#[CsrfProtected('purge')]
class PurgeController extends AbstractController
{
    public function __invoke(
        string $contestId,
        LoggedUser $loggedUser,
        PurgeContestPersonalDataService $service,
    ): Response {
        try {
            $service(new PurgeContestPersonalDataRequest(new ContestId($contestId)), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Datos personales purgados con éxito'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (\RuntimeException) {
            $this->addFlash('danger', new TranslatableMessage('No se han podido purgar los datos personales'));
        }

        return $this->redirectToRoute('contests_entries', ['contestId' => $contestId]);
    }
}
