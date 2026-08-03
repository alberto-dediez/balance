<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Project;

use App\Balance\Application\Project\Register\RegisterProjectService;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Infrastructure\Symfony\Form\RegisterProjectType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/projects/register', name: 'admin_projects_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterProjectService $registerProject, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Project(ProjectId::generate()));

        $form = $this->createForm(RegisterProjectType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerProject, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerProject($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Proyecto creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_projects');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/admin/projects/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
