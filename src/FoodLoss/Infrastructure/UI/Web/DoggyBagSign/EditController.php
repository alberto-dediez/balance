<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagSign;

use App\FoodLoss\Application\DoggyBagSign\Update\UpdateDoggyBagSignRequest;
use App\FoodLoss\Application\DoggyBagSign\Update\UpdateDoggyBagSignService;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateDoggyBagSignType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/doggy-bag-signs/edit/{doggyBagSignId}', name: 'doggy_bag_signs_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetDoggyBagSignFromIdTrait;

    public function __invoke(
        string $doggyBagSignId,
        DoggyBagSignRepository $doggyBagSignRepository,
        UpdateDoggyBagSignService $updateDoggyBagSign,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = $loggedUser();
        $doggyBagSign = $this->getDoggyBagSignFromId($doggyBagSignId, $doggyBagSignRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $doggyBagSign);

        $updateRequest = new UpdateDoggyBagSignRequest(
            doggyBagSignId: $doggyBagSign->id(),
            name: $doggyBagSign->name(),
            location: $doggyBagSign->location(),
            propertyId: $doggyBagSign->propertyId(),
        );

        $form = $this->createForm(UpdateDoggyBagSignType::class, $updateRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateDoggyBagSign, $user): void {
                /** @var UpdateDoggyBagSignRequest $updateRequest */
                $updateRequest = $form->getData();

                /** @var UploadedFile|null $file */
                $file = $form->get('pdfFile')->getData();

                if ($file) {
                    $updateRequest->temporaryFilePath = $file->getPathname();
                    $updateRequest->fileExtension = (string) $file->guessExtension();
                }

                $updateDoggyBagSign($updateRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Cartel Doggy Bag actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('doggy_bag_signs');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/doggy-bag-signs/edit.html.twig', [
            'doggyBagSign' => $doggyBagSign,
            'form' => $form->createView(),
        ]);
    }
}
