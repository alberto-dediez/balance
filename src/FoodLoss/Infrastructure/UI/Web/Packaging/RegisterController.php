<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Packaging;

use App\FoodLoss\Application\Packaging\Register\RegisterPackagingRequest;
use App\FoodLoss\Application\Packaging\Register\RegisterPackagingService;
use App\FoodLoss\Domain\Model\Packaging\Packaging;
use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\FoodLoss\Domain\Model\Packaging\PackagingPropertyStockData;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterPackagingType;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/packagings/register', name: 'packagings_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterPackagingService $registerPackaging, LoggedUser $loggedUser, LoggedAccount $loggedAccount, PropertyRepository $propertyRepository, Request $request): Response
    {
        $account = $loggedAccount();

        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Packaging(
            id: PackagingId::generate(),
            accountId: $account?->id(),
        ));

        $properties = $propertyRepository->findByFilter(new PropertyFilter(
            accountId: $account?->id(),
            limit: 0,
        ));

        $stocks = array_map(
            fn($property) => new PackagingPropertyStockData($property->id()),
            $properties,
        );

        $form = $this->createForm(RegisterPackagingType::class, new RegisterPackagingRequest(stocks: $stocks));

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerPackaging, $loggedUser, $account): true {
                $registerRequest = $form->getData();

                if (null === $account) {
                    throw new \InvalidArgumentException('No account found for logged user');
                }

                $registerRequest->accountId = $account->id();

                $response = $registerPackaging($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Envase creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('packagings');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/packagings/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
