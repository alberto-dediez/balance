<?php

namespace App\Waste\Infrastructure\UI\Web\Container;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Container\PrintAllQr\PrintAllQrContainerRequest;
use App\Waste\Application\Container\PrintAllQr\PrintAllQrContainerService;

#[Route('/containers/print-all', name: 'containers_print_all')]
class PrintAllController extends AbstractController
{
    public function __invoke(
        PrintAllQrContainerService $printAllContainer, 
        LoggedAccount $loggedAccount,
        LoggedUser $loggedUser,
        Request $request
    ): Response
    {   
        $containerRequest = new PrintAllQrContainerRequest(
            accountId: $loggedAccount()?->id()
        );

        try {
            $response = $printAllContainer($containerRequest, $loggedUser());

            if (null === $response || null === $response->path || empty($response->path)) {
                throw new \RuntimeException("Invalid response");
            }

            return $this->file($response->path, "print_all.pdf", ResponseHeaderBag::DISPOSITION_INLINE);            
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            dump($e);die();

            $this->addFlash('danger', new TranslatableMessage("No se ha podido generar los carteles de los contenedores de la cuenta"));
        }

        return $this->redirectToRoute('containers');
    }
}
