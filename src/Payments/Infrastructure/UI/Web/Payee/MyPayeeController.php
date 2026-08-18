<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Payee;

use App\Payments\Application\Payee\Register\RegisterPayeeRequest;
use App\Payments\Application\Payee\Register\RegisterPayeeService;
use App\Payments\Application\Payee\Update\UpdatePayeeRequest;
use App\Payments\Application\Payee\Update\UpdatePayeeService;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Infrastructure\Symfony\Form\RegisterMyPayeeType;
use App\Payments\Infrastructure\Symfony\Form\UpdateMyPayeeType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/my-payee', name: 'my_payee')]
class MyPayeeController extends AbstractController
{
    use EditTrait;
    use RegisterTrait;

    public function __invoke(
        PayeeRepository $payeeRepository,
        RegisterPayeeService $registerPayee,
        UpdatePayeeService $updatePayee,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $accountId = $loggedUser()->accountId();

        if (!$accountId instanceof AccountId) {
            throw $this->createAccessDeniedException('El usuario no pertenece a ninguna cuenta');
        }

        $payee = $payeeRepository->findOneBySubject(PayeeType::Account, (string) $accountId);

        try {
            $response = $payee instanceof Payee
                ? $this->update($payee, $updatePayee, $loggedUser, $request)
                : $this->create($accountId, $registerPayee, $loggedUser, $request);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $response;
    }

    private function create(
        AccountId $accountId,
        RegisterPayeeService $registerPayee,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $form = $this->createForm(RegisterMyPayeeType::class, new RegisterPayeeRequest(
            type: PayeeType::Account,
            subjectId: (string) $accountId,
        ));

        $needsRedirect = $this->register($request, $form, function ($form) use ($registerPayee, $loggedUser) {
            $registerPayee($form->getData(), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Datos de cobro guardados con éxito'));

            return true;
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('my_payee');
        }

        return $this->render('@payments/payee/my-payee.html.twig', [
            'payee' => null,
            'form' => $form->createView(),
        ]);
    }

    private function update(
        Payee $payee,
        UpdatePayeeService $updatePayee,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $form = $this->createForm(UpdateMyPayeeType::class, new UpdatePayeeRequest(
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
        ));

        $needsRedirect = $this->edit($request, $form, function ($form) use ($updatePayee, $loggedUser) {
            $updatePayee($form->getData(), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Datos de cobro guardados con éxito'));
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('my_balance');
        }

        return $this->render('@payments/payee/my-payee.html.twig', [
            'payee' => $payee,
            'form' => $form->createView(),
        ]);
    }
}
