<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Company;

use App\Property\Application\Company\Remove\RemoveCompanyRequest;
use App\Property\Application\Company\Remove\RemoveCompanyService;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/companies/remove/{companyId}', name: 'admin_companies_remove')]
class RemoveController extends AbstractController
{
    use GetCompanyFromIdTrait;

    public function __invoke(
        string $companyId,
        CompanyRepository $companyRepository,
        RemoveCompanyService $removeCompany,
        LoggedUser $loggedUser,
    ): Response {
        $company = $this->getCompanyFromId($companyId, $companyRepository);

        try {
            $removeCompany(new RemoveCompanyRequest($company->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la empresa {company}', ['{company}' => $company->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la empresa {company}', ['{company}' => $company->name()]));
        }

        return $this->redirectToRoute('admin_companies');
    }
}
