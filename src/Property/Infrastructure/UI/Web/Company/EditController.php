<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Company;

use App\Property\Application\Company\Update\UpdateCompanyRequest;
use App\Property\Application\Company\Update\UpdateCompanyService;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Infrastructure\Symfony\Form\UpdateCompanyType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/companies/edit/{companyId}', name: 'companies_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetCompanyFromIdTrait;

    public function __invoke(
        string $companyId,
        CompanyRepository $companyRepository,
        UpdateCompanyService $updateCompany,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null === $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $company = $this->getCompanyFromId($companyId, $companyRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $company);

        $companyRequest = new UpdateCompanyRequest(
            companyId: $company->id(),
            name: $company->name(),
            vatNumber: $company->vatNumber(),
            street: $company->address()?->street() ?? '',
            postalCode: $company->address()?->postalCode() ?? '',
            city: $company->address()?->city() ?? '',
            country: $company->address()?->country() ?? '',
        );

        try {
            $form = $this->createForm(UpdateCompanyType::class, $companyRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateCompany, $loggedUser): void {
                $updateRequest = $form->getData();

                $updateCompany($updateRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Empresa actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('companies');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/companies/edit.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }
}
