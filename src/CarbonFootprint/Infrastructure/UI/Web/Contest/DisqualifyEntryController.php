<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Application\Contest\View\ViewContestRequest;
use App\CarbonFootprint\Application\Contest\View\ViewContestService;
use App\CarbonFootprint\Application\ContestEntry\Disqualify\DisqualifyContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\Disqualify\DisqualifyContestEntryService;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\DisqualifyContestEntryType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/carbon-footprint/contests/{contestId}/entries/{entryId}/disqualify', name: 'contests_entries_disqualify', methods: ['GET', 'POST'])]
class DisqualifyEntryController extends AbstractController
{
    public function __invoke(
        string $contestId,
        string $entryId,
        LoggedUser $loggedUser,
        ViewContestService $viewContest,
        ContestEntryRepository $contestEntryRepository,
        DisqualifyContestEntryService $disqualifyContestEntry,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $user = $loggedUser();

        try {
            $contest = $viewContest(new ViewContestRequest(new ContestId($contestId)), $user)->contest;
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $entry = $contestEntryRepository->find(new ContestEntryId($entryId));

        if (!$entry instanceof ContestEntry || !($entry->contestId()?->equals($contest->id()) ?? false)) {
            throw $this->createNotFoundException('Contest entry not found');
        }

        $form = $this->createForm(DisqualifyContestEntryType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $disqualifyContestEntry(new DisqualifyContestEntryRequest($contest->id(), $entry->id(), (string) $form->get('reason')->getData()), $user);

                $this->addFlash('success', new TranslatableMessage('Inscripción descalificada con éxito'));

                return $this->redirectToRoute('contests_entries', ['contestId' => $contestId]);
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException|\RuntimeException $e) {
                $form->addError(new FormError($translator->trans($e->getMessage())));
            }
        }

        return $this->render('@carbon_footprint/contests/disqualify.html.twig', [
            'contest' => $contest,
            'entry' => $entry,
            'form' => $form->createView(),
        ]);
    }
}
