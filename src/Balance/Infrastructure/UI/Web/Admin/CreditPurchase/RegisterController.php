<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\CreditPurchase;

use App\Balance\Application\CreditPurchase\Assign\AssignCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Assign\AssignCreditPurchaseService;
use App\Balance\Domain\Model\CreditPurchase\CompensationNotEnabledForAccountException;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\DuplicatedPurchaseReferenceException;
use App\Balance\Domain\Model\Project\ProjectFilter;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Infrastructure\Symfony\Form\AssignCreditPurchaseType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/credits/register', name: 'admin_credit_purchases_register', priority: 1)]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        AssignCreditPurchaseService $assignCreditPurchase,
        ProjectRepository $projectRepository,
        LoggedUser $loggedUser,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(SecurityAction::Admin->value, new CreditPurchase(id: CreditPurchaseId::generate()));

        $projectChoices = [];

        foreach ($projectRepository->findByFilter(new ProjectFilter(projectType: ProjectType::EmissionCompensation, limit: 0)) as $project) {
            $projectChoices[$project->name()] = $project->id();
        }

        try {
            $form = $this->createForm(AssignCreditPurchaseType::class, new AssignCreditPurchaseRequest(), [
                'projects' => $projectChoices,
            ]);

            $needsRedirect = $this->register($request, $form, function (FormInterface $form) use ($assignCreditPurchase, $loggedUser, $translator): bool {
                try {
                    $response = $assignCreditPurchase($form->getData(), $loggedUser());
                } catch (DuplicatedPurchaseReferenceException) {
                    $form->get('reference')->addError(new FormError($translator->trans(
                        'El identificador {reference} ya está registrado en otra compra.',
                        ['{reference}' => $form->get('reference')->getData()],
                    )));

                    return false;
                } catch (CompensationNotEnabledForAccountException) {
                    $form->get('accountId')->addError(new FormError($translator->trans('La cuenta no tiene activada la funcionalidad de compensación')));

                    return false;
                }

                if (!$response->validatedPendingRequest) {
                    $this->addFlash('success', new TranslatableMessage('Créditos asignados correctamente'));

                    return true;
                }

                $this->addFlash('success', $response->partiallyFulfilled
                    ? new TranslatableMessage('Créditos asignados: se ha validado parcialmente la solicitud pendiente de la cuenta')
                    : new TranslatableMessage('Créditos asignados: se ha validado la solicitud pendiente de la cuenta'));

                if ($response->remainingPendingRequests > 0) {
                    $this->addFlash('info', new TranslatableMessage(
                        '{count, plural, one {La cuenta mantiene # solicitud pendiente para este proyecto} other {La cuenta mantiene # solicitudes pendientes para este proyecto}}',
                        ['count' => $response->remainingPendingRequests],
                    ));
                }

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_credit_purchases');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/admin/credits/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
