<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CateringItem;

use App\CarbonFootprint\Application\CateringItem\Register\RegisterCateringItemService;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterCateringItemType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/catering-items/register', name: 'admin_catering_items_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterCateringItemService $registerCateringItem, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new CateringItem(CateringItemId::generate()));

        $form = $this->createForm(RegisterCateringItemType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerCateringItem, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerCateringItem($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Elemento de catering creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_catering_items');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/catering_items/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
