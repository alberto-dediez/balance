<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Company;

use App\Property\Application\Company\Register\RegisterCompanyRequest;
use App\Property\Application\Company\Register\RegisterCompanyService;
use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Infrastructure\Symfony\Form\RegisterCompanyType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/companies/register', name: 'companies_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterCompanyService $registerCompany,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Company(
            id: CompanyId::generate(),
            accountId: $account->id(),
        ));

        $form = $this->createForm(RegisterCompanyType::class, new RegisterCompanyRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerCompany, $account, $loggedUser) {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $account->id();

                $response = $registerCompany($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Empresa creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('companies');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/companies/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
