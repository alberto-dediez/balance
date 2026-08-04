<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media;

use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Application\Media\Upload\UploadMediaRequest;
use App\Library\Application\Media\Upload\UploadMediaResponse;
use App\Library\Application\Media\Upload\UploadMediaService;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Infrastructure\Symfony\Form\UploadMediaType;
use App\Library\Infrastructure\Symfony\Http\UploadedFileMapper;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/library/media/upload', name: 'library_media_upload')]
#[IsGranted('ROLE_USER')]
class UploadController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        UploadMediaService $uploadMedia,
        MediaAuthorizer $authorizer,
        UploadedFileMapper $uploadedFileMapper,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $user = $loggedUser();
        $account = $loggedAccount();

        $media = new Media(MediaId::generate(), $account?->id());

        $form = $this->createForm(UploadMediaType::class, null, [
            'manage_visibility' => $authorizer->canDoThat($media, Action::Admin, $user),
        ]);

        try {
            $authorizer->denyUnlessCanDoThat($media, Action::Register, $user);

            $needsRedirect = $this->register($request, $form, function ($form) use ($uploadMedia, $uploadedFileMapper, $user, $account): true {
                /** @var UploadedFile[] $uploadedFiles */
                $uploadedFiles = $form->get('file')->getData();

                /** @var UploadMediaRequest $formRequest */
                $formRequest = $form->getData();

                $keepMetadata = 1 === count($uploadedFiles);

                /** @var UploadMediaResponse[] $responses */
                $responses = [];

                foreach ($uploadedFiles as $uploadedFile) {
                    $uploadRequest = new UploadMediaRequest(
                        name: $keepMetadata ? $formRequest->name : '',
                        description: $keepMetadata ? $formRequest->description : null,
                        alternativeText: $keepMetadata ? $formRequest->alternativeText : null,
                        visibility: $formRequest->visibility,
                    );

                    $uploadRequest = $uploadedFileMapper($uploadedFile, $account?->id(), $user->id(), $uploadRequest);

                    $responses[] = $uploadMedia($uploadRequest, $user);
                }

                $names = implode(', ', array_map(static fn(UploadMediaResponse $response): string => $response->name, $responses));

                $message = $keepMetadata
                    ? new TranslatableMessage('Archivo subido correctamente: {name}', ['{name}' => $names])
                    : new TranslatableMessage('Archivos subidos correctamente: {names}', ['{names}' => $names]);

                $this->addFlash('success', $message);

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('library_media');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@library/media/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
