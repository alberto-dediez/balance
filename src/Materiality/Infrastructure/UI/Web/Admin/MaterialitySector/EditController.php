<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialitySector;

use App\Materiality\Application\MaterialitySector\Update\UpdateMaterialitySectorRequest;
use App\Materiality\Application\MaterialitySector\Update\UpdateMaterialitySectorService;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Materiality\Infrastructure\Symfony\Form\UpdateMaterialitySectorType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materiality-sectors/edit/{materialitySectorId}', name: 'admin_materiality_sectors_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetMaterialitySectorFromIdTrait;

    public function __invoke(
        string $materialitySectorId,
        MaterialitySectorRepository $materialitySectorRepository,
        UpdateMaterialitySectorService $updateMaterialitySector,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $materialitySector = $this->getMaterialitySectorFromId($materialitySectorId, $materialitySectorRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $materialitySector);

        $materialitySectorRequest = new UpdateMaterialitySectorRequest(
            materialitySectorId: $materialitySector->id(),
            name: $materialitySector->name(),
        );

        try {
            $form = $this->createForm(UpdateMaterialitySectorType::class, $materialitySectorRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMaterialitySector, $loggedUser) {
                $updateMaterialitySector($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Sector de materialidad actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_materiality_sectors');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@materiality/admin/materiality-sectors/edit.html.twig', [
            'materialitySector' => $materialitySector,
            'form' => $form->createView(),
        ]);
    }
}
