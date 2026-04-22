<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Company;

use App\Property\Application\Company\Remove\RemoveCompanyRequest;
use App\Property\Application\Company\Remove\RemoveCompanyService;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/companies/remove/{companyId}', name: 'companies_remove')]
class RemoveController extends AbstractController
{
    use GetCompanyFromIdTrait;

    public function __invoke(
        string $companyId,
        CompanyRepository $companyRepository,
        RemoveCompanyService $removeCompany,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $company = $this->getCompanyFromId($companyId, $companyRepository);

        try {
            $removeCompany(new RemoveCompanyRequest($company->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la empresa {company}', ['{company}' => $company->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la empresa {company}', ['{company}' => $company->name()]));
        }

        return $this->redirectToRoute('companies');
    }
}
