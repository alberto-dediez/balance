<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Incidence;

use App\FoodLoss\Application\Incidence\AddEvidence\AddIncidenceEvidenceRequest;
use App\FoodLoss\Application\Incidence\AddEvidence\AddIncidenceEvidenceService;
use App\FoodLoss\Application\Incidence\RemoveEvidence\RemoveIncidenceEvidenceRequest;
use App\FoodLoss\Application\Incidence\RemoveEvidence\RemoveIncidenceEvidenceService;
use App\FoodLoss\Application\Incidence\Update\UpdateIncidenceRequest;
use App\FoodLoss\Application\Incidence\Update\UpdateIncidenceService;
use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\Data\EvidenceData;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateIncidenceType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/incidences/edit/{incidenceId}', name: 'incidences_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetIncidenceFromIdTrait;

    public function __invoke(
        string $incidenceId,
        IncidenceRepository $incidenceRepository,
        UpdateIncidenceService $updateIncidence,
        AddIncidenceEvidenceService $addIncidenceEvidence,
        RemoveIncidenceEvidenceService $removeIncidenceEvidence,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $incidence = $this->getIncidenceFromId($incidenceId, $incidenceRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $incidence);

        $account = $loggedAccount();
        assert($account instanceof \App\Security\Domain\Model\Account\Account);

        $propertyId = $incidence->propertyId();
        assert($propertyId instanceof PropertyId);

        $incidenceRequest = new UpdateIncidenceRequest(
            incidenceId: $incidence->id(),
            name: $incidence->name(),
            exceptionOccurredOn: $incidence->exceptionOccurredOn(),
            incidenceType: $incidence->incidenceType(),
            description: $incidence->description(),
            effect: $incidence->effect(),
            propertyId: $propertyId,
        );

        $existingEvidenceDatas = array_values(array_map(static function ($evidence): EvidenceData {
            $data = new EvidenceData();
            $data->id = (string) $evidence->id();
            $data->originalName = $evidence->originalName();

            return $data;
        }, $incidence->evidences()->toArray()));

        try {
            $form = $this->createForm(UpdateIncidenceType::class, $incidenceRequest, [
                'existing_evidence_datas' => $existingEvidenceDatas,
            ]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateIncidence, $addIncidenceEvidence, $removeIncidenceEvidence, $loggedUser, $account, $incidence): void {
                $incidenceRequest = $form->getData();

                $updateIncidence($incidenceRequest, $loggedUser());

                /** @var iterable<int, EvidenceData> $evidences */
                $evidences = $form->get('evidences')->getData();

                $keptIds = [];
                $newEvidences = [];

                foreach ($evidences as $evidenceData) {
                    if (null !== $evidenceData->id) {
                        $keptIds[(string) $evidenceData->id] = $evidenceData->id;
                    } elseif ($evidenceData->file instanceof UploadedFile) {
                        $newEvidences[] = $evidenceData;
                    }
                }

                foreach ($incidence->evidences()->toArray() as $existing) {
                    if (!isset($keptIds[(string) $existing->id()])) {
                        $removeIncidenceEvidence(new RemoveIncidenceEvidenceRequest(
                            incidenceId: $incidence->id(),
                            evidenceId: new IncidenceEvidenceId((string) $existing->id()),
                        ), $loggedUser());
                    }
                }

                foreach ($newEvidences as $evidenceData) {
                    /** @var UploadedFile $file */
                    $file = $evidenceData->file;

                    $addIncidenceEvidence(new AddIncidenceEvidenceRequest(
                        incidenceId: $incidence->id(),
                        accountId: $account->id(),
                        temporaryFilePath: $file->getPathname(),
                        fileExtension: (string) $file->guessExtension(),
                        originalName: $file->getClientOriginalName(),
                        mimeType: (string) $file->getMimeType(),
                    ), $loggedUser());
                }

                $this->addFlash('success', new TranslatableMessage('Incidencia actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('incidences');
            }
            $existingEvidenceDatas = array_values(array_map(static function ($evidence): EvidenceData {
                $data = new EvidenceData();
                $data->id = (string) $evidence->id();
                $data->originalName = $evidence->originalName();

                return $data;
            }, $incidence->evidences()->toArray()));

            $form = $this->createForm(UpdateIncidenceType::class, $incidenceRequest, [
                'existing_evidence_datas' => $existingEvidenceDatas,
            ]);

        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/incidences/edit.html.twig', [
            'incidence' => $incidence,
            'form' => $form->createView(),
        ]);
    }
}
