<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Application\Contest\List\ListContestsRequest;
use App\CarbonFootprint\Application\Contest\List\ListContestsService;
use App\CarbonFootprint\Application\Contest\Register\RegisterContestRequest;
use App\CarbonFootprint\Application\Contest\Register\RegisterContestService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ContestType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/carbon-footprint/contests/register', name: 'contests_register', methods: ['GET', 'POST'])]
class RegisterController extends AbstractController
{
    public function __invoke(
        LoggedUser $loggedUser,
        RegisterContestService $registerContest,
        ListContestsService $listContests,
        CurrentAccountIdProvider $currentAccountIdProvider,
        TripCalculationLinkRepository $tripCalculationLinkRepository,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $user = $loggedUser();

        try {
            $listContests(new ListContestsRequest(limit: 1), $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $form = $this->createForm(ContestType::class, new ContestFormData());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $accountId = $currentAccountIdProvider->currentAccountId();
            $tripLinkId = $accountId instanceof AccountId ? $tripCalculationLinkRepository->findForAccount($accountId)?->id() : null;

            try {
                $contest = $registerContest(new RegisterContestRequest(
                    participationMode: $data->participationMode,
                    awardMode: $data->awardMode,
                    winnersCount: $data->winnersCount,
                    platformDraw: $data->platformDraw,
                    adultsOnly: $data->adultsOnly,
                    name: $data->name,
                    description: $data->description,
                    prizeDescription: $data->prizeDescription,
                    terms: $data->terms,
                    organizerName: $data->organizerName,
                    organizerTaxId: $data->organizerTaxId,
                    organizerContactEmail: $data->organizerContactEmail,
                    privacyPolicyUrl: $data->privacyPolicyUrl,
                    startsAt: $data->startsAt(),
                    endsAt: $data->endsAt(),
                    hosts: $data->hosts($tripLinkId),
                    allowedCountries: $data->allowedCountries,
                ), $user)->contest;

                $this->addFlash('success', new TranslatableMessage('Concurso registrado con éxito'));

                return $this->redirectToRoute($this->clickedSaveAndReturn($form) ? 'contests' : 'contests_edit', ['contestId' => (string) $contest->id()]);
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException|\RuntimeException $e) {
                $form->addError(new FormError($translator->trans($e->getMessage())));
            }
        }

        return $this->render('@carbon_footprint/contests/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /** @param FormInterface<ContestFormData> $form */
    private function clickedSaveAndReturn(FormInterface $form): bool
    {
        assert($form instanceof Form);
        $button = $form->getClickedButton();

        return $button instanceof FormInterface && 'saveAndReturn' === $button->getName();
    }
}
