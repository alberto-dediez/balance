<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagSign;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Security\Application\Action;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/doggy-bag-signs/download/{doggyBagSignId}', name: 'doggy_bag_signs_download')]
class DownloadController extends AbstractController
{
    use GetDoggyBagSignFromIdTrait;

    public function __invoke(
        string $doggyBagSignId,
        DoggyBagSignRepository $repository,
        LoggedUser $loggedUser,
    ): Response {
        $doggyBagSign = $this->getDoggyBagSignFromId($doggyBagSignId, $repository);

        $this->denyAccessUnlessGranted(Action::View->value, $doggyBagSign);

        $path = $this->getParameter('app.uploads_dir') . '/' . $doggyBagSign->path();

        if (!file_exists($path)) {
            throw $this->createNotFoundException('El fichero no existe en el servidor.');
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'cartel-doggy-bag-' . $doggyBagSign->id() . '.pdf',
        );

        return $response;
    }
}
