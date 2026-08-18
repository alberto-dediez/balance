<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payee;

use App\Payments\Application\Payee\Update\UpdatePayeeRequest;
use App\Payments\Application\Payee\Update\UpdatePayeeService;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeSubjectDirectory;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Infrastructure\Symfony\Form\UpdatePayeeType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/payees/edit/{payeeId}', name: 'admin_payees_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetPayeeFromIdTrait;

    public function __invoke(
        string $payeeId,
        PayeeRepository $payeeRepository,
        PayeeSubjectDirectory $subjectDirectory,
        UpdatePayeeService $updatePayee,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $payee = $this->getPayeeFromId($payeeId, $payeeRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Admin->value, $payee);

        $updateRequest = new UpdatePayeeRequest(
            payeeId: $payee->id(),
            legalName: $payee->legalName(),
            taxId: $payee->taxId(),
            street: $payee->address()->street(),
            postalCode: $payee->address()->postalCode(),
            city: $payee->address()->city(),
            country: $payee->address()->country(),
            iban: $payee->iban(),
            bic: $payee->bic(),
            holderName: $payee->holderName(),
            notes: $payee->notes(),
        );

        try {
            $form = $this->createForm(UpdatePayeeType::class, $updateRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updatePayee, $loggedUser) {
                $updatePayee($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Beneficiario actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_payees');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@payments/admin/payee/edit.html.twig', [
            'payee' => $payee,
            'subjectLabel' => $this->subjectLabel($payee, $subjectDirectory),
            'form' => $form->createView(),
        ]);
    }

    private function subjectLabel(Payee $payee, PayeeSubjectDirectory $subjectDirectory): ?string
    {
        $type = $payee->type();

        if (!$type instanceof PayeeType) {
            return null;
        }

        return $subjectDirectory->label($type, (string) $payee->subjectId());
    }
}
