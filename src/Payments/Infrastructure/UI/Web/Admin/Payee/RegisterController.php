<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payee;

use App\Payments\Application\Payee\Register\RegisterPayeeService;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Infrastructure\Symfony\Form\RegisterPayeeType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/payees/register', name: 'admin_payees_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterPayeeService $registerPayee, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Admin->value, new Payee(id: PayeeId::generate()));

        $form = $this->createForm(RegisterPayeeType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerPayee, $loggedUser) {
                $response = $registerPayee($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage(
                    'Beneficiario creado correctamente: {name}',
                    ['{name}' => $response->legalName],
                ));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_payees');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@payments/admin/payee/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
