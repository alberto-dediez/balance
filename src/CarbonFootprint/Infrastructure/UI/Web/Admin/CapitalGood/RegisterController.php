<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CapitalGood;

use App\CarbonFootprint\Application\CapitalGood\Register\RegisterCapitalGoodService;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterCapitalGoodType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/capital-goods/register', name: 'admin_capital_goods_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterCapitalGoodService $registerCapitalGood, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new CapitalGood(CapitalGoodId::generate()));

        $form = $this->createForm(RegisterCapitalGoodType::class);

        try {
            $capitalGoodId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerCapitalGood, $loggedUser, &$capitalGoodId) {
                $registerRequest = $form->getData();

                $response = $registerCapitalGood($registerRequest, $loggedUser());

                $capitalGoodId = $response->capitalGoodId;

                $this->addFlash('success', new TranslatableMessage('Bien de capital creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($capitalGoodId instanceof CapitalGoodId) {
                    return $this->redirectToRoute('admin_capital_goods_edit', ['capitalGoodId' => $capitalGoodId]);
                }

                return $this->redirectToRoute('admin_capital_goods');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/capital_goods/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
