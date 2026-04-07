<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Protocol;

use App\FoodLoss\Application\Protocol\Update\UpdateProtocolRequest;
use App\FoodLoss\Application\Protocol\Update\UpdateProtocolService;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateProtocolType;
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

#[Route('/protocols/edit/{protocolId}', name: 'protocols_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetProtocolFromIdTrait;

    public function __invoke(
        string $protocolId,
        ProtocolRepository $protocolRepository,
        UpdateProtocolService $updateProtocol,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $protocol = $this->getProtocolFromId($protocolId, $protocolRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $protocol);

        $updateRequest = new UpdateProtocolRequest(
            protocolId: $protocol->id(),
            name: $protocol->name(),
            description: $protocol->description(),
        );

        $form = $this->createForm(UpdateProtocolType::class, $updateRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateProtocol, $user): void {
                /** @var UpdateProtocolRequest $updateRequest */
                $updateRequest = $form->getData();

                /** @var UploadedFile|null $file */
                $file = $form->get('file')->getData();

                if ($file) {
                    $updateRequest->temporaryFilePath = $file->getPathname();
                    $updateRequest->fileExtension = (string) $file->guessExtension();
                }

                $updateProtocol($updateRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Protocolo actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('protocols');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/protocol/edit.html.twig', [
            'protocol' => $protocol,
            'form' => $form->createView(),
        ]);
    }
}
