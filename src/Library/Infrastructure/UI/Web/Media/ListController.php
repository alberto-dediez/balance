<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media;

use App\Library\Application\Media\List\ListMediaRequest;
use App\Library\Application\Media\List\ListMediaService;
use App\Library\Infrastructure\Symfony\Form\ListMediaType;
use App\Library\Infrastructure\UI\Web\Media\Bulk\RemoveMedia;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/library/media', name: 'library_media')]
#[IsGranted('ROLE_USER')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListMediaService $listMedia, LoggedUser $loggedUser, Request $request): Response
    {
        [$filter, $form] = $this->filter($request, fn(): ListMediaRequest => new ListMediaRequest(), ListMediaType::class);

        try {
            $response = $listMedia($filter, $loggedUser());
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->media,
            'actions' => [
                new RemoveMedia(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@library/media/list.html.twig', [
            'bulkForm' => $bulkForm,
            'media' => $response->media,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
