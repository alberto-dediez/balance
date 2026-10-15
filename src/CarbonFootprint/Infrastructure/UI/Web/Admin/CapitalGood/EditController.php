<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CapitalGood;

use App\CarbonFootprint\Application\CapitalGood\Update\EmissionFactorData;
use App\CarbonFootprint\Application\CapitalGood\Update\UpdateCapitalGoodRequest;
use App\CarbonFootprint\Application\CapitalGood\Update\UpdateCapitalGoodService;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateCapitalGoodType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/capital-goods/edit/{capitalGoodId}', name: 'admin_capital_goods_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetCapitalGoodFromIdTrait;

    public function __invoke(
        string $capitalGoodId,
        CapitalGoodRepository $capitalGoodRepository,
        UpdateCapitalGoodService $updateCapitalGood,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $capitalGood = $this->getCapitalGoodFromId($capitalGoodId, $capitalGoodRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $capitalGood);

        $emissionFactors = [];
        foreach ($capitalGood->emissionFactors() as $capitalGoodEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromCapitalGoodEmissionFactor($capitalGoodEmissionFactor);
        }

        $capitalGoodRequest = new UpdateCapitalGoodRequest(
            capitalGoodId: $capitalGood->id(),
            name: $capitalGood->name(),
            category: $capitalGood->category(),
            unit: $capitalGood->unit(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateCapitalGoodType::class, $capitalGoodRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateCapitalGood, $loggedUser) {
                $capitalGoodRequest = $form->getData();

                $updateCapitalGood($capitalGoodRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Bien de capital actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_capital_goods');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/capital_goods/edit.html.twig', [
            'capitalGood' => $capitalGood,
            'form' => $form->createView(),
        ]);
    }
}
