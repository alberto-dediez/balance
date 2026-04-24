<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Company;

use App\Property\Application\Company\Update\UpdateCompanyRequest;
use App\Property\Application\Company\Update\UpdateCompanyService;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Infrastructure\Symfony\Form\AdminUpdateCompanyType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/companies/edit/{companyId}', name: 'admin_companies_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetCompanyFromIdTrait;

    public function __invoke(
        string $companyId,
        CompanyRepository $companyRepository,
        UpdateCompanyService $updateCompany,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $company = $this->getCompanyFromId($companyId, $companyRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $company);

        $companyRequest = new UpdateCompanyRequest(
            companyId: $company->id(),
            name: $company->name(),
            vatNumber: $company->vatNumber(),
            street: $company->address()->street() ?? '',
            postalCode: $company->address()->postalCode() ?? '',
            city: $company->address()->city() ?? '',
            country: $company->address()->country() ?? '',
        );

        try {
            $form = $this->createForm(AdminUpdateCompanyType::class, $companyRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateCompany, $loggedUser): void {
                $updateCompany($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Empresa actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_companies');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/companies/edit.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }
}
