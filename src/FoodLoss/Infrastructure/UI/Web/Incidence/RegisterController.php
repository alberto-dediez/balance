<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Incidence;

use App\FoodLoss\Application\Incidence\AddEvidence\AddIncidenceEvidenceRequest;
use App\FoodLoss\Application\Incidence\AddEvidence\AddIncidenceEvidenceService;
use App\FoodLoss\Application\Incidence\Register\RegisterIncidenceRequest;
use App\FoodLoss\Application\Incidence\Register\RegisterIncidenceService;
use App\FoodLoss\Domain\Model\Incidence\Incidence;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceType;
use App\FoodLoss\Infrastructure\Symfony\Form\Data\EvidenceData;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterIncidenceType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/incidences/register', name: 'incidences_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterIncidenceService $registerIncidence,
        AddIncidenceEvidenceService $addIncidenceEvidence,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Incidence(IncidenceId::generate()));

        $account = $loggedAccount();
        assert($account instanceof \App\Security\Domain\Model\Account\Account);

        $registerRequest = new RegisterIncidenceRequest(
            name: '',
            exceptionOccurredOn: new \DateTimeImmutable('NOW'),
            incidenceType: IncidenceType::MachineryFailure,
            description: '',
            effect: '',
            propertyId: PropertyId::generate(),
            accountId: $account->id(),
        );

        $form = $this->createForm(RegisterIncidenceType::class, $registerRequest);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerIncidence, $addIncidenceEvidence, $loggedUser, $account) {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $account->id();

                $response = $registerIncidence($registerRequest, $loggedUser());

                /** @var iterable<int, EvidenceData> $evidences */
                $evidences = $form->get('evidences')->getData();

                foreach ($evidences as $evidenceData) {
                    if (!$evidenceData->file instanceof UploadedFile) {
                        continue;
                    }

                    $addIncidenceEvidence(new AddIncidenceEvidenceRequest(
                        incidenceId: $response->incidenceId,
                        accountId: $account->id(),
                        temporaryFilePath: $evidenceData->file->getPathname(),
                        fileExtension: (string) $evidenceData->file->guessExtension(),
                        originalName: $evidenceData->file->getClientOriginalName(),
                        mimeType: (string) $evidenceData->file->getMimeType(),
                    ), $loggedUser());
                }

                $this->addFlash('success', new TranslatableMessage('Incidencia creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('incidences');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/incidences/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
