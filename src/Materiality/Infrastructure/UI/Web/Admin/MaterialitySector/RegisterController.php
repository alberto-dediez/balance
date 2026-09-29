<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialitySector;

use App\Materiality\Application\MaterialitySector\Register\RegisterMaterialitySectorService;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Infrastructure\Symfony\Form\RegisterMaterialitySectorType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materiality-sectors/register', name: 'admin_materiality_sectors_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterMaterialitySectorService $registerMaterialitySector,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new MaterialitySector(MaterialitySectorId::generate()));

        $form = $this->createForm(RegisterMaterialitySectorType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerMaterialitySector, $loggedUser) {
                $response = $registerMaterialitySector($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Sector de materialidad creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_materiality_sectors');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@materiality/admin/materiality-sectors/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
