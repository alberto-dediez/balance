<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Account\Register\RegisterAccountRequest;
use App\Security\Application\Account\Register\RegisterAccountService;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Form\RegisterAccountType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/accounts/register', name: 'admin_accounts_register')]
#[IsGranted('ROLE_ADMIN')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterAccountService $registerAccount,
        AccountAuthorizer $authorizer,
        LoggedUser $loggedUser,
        EmissionFactorGroupRepository $emissionFactorGroupRepository,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $registerRequest = new RegisterAccountRequest(
            active: true,
            emissionFactorGroupId: $emissionFactorGroupRepository->findDefault()?->id(),
        );

        $form = $this->createForm(RegisterAccountType::class, $registerRequest);

        try {
            $account = new Account(AccountId::generate());

            $authorizer->denyUnlessCanDoThat($account, Action::Register, $user);

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerAccount, $user): true {
                $registerRequest = $form->getData();

                $response = $registerAccount($registerRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Cuenta creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_accounts');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@security/admin/accounts/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
