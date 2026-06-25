<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MerchandisingItem;

use App\CarbonFootprint\Application\MerchandisingItem\Register\RegisterMerchandisingItemService;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterMerchandisingItemType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/merchandising-items/register', name: 'admin_merchandising_items_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterMerchandisingItemService $registerMerchandisingItem, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new MerchandisingItem(MerchandisingItemId::generate()));

        $form = $this->createForm(RegisterMerchandisingItemType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerMerchandisingItem, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerMerchandisingItem($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Artículo de merchandising creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_merchandising_items');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/merchandising_items/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
