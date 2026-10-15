<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Process;

use App\CarbonFootprint\Application\Process\Register\RegisterProcessService;
use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterProcessType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/processes/register', name: 'admin_processes_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterProcessService $registerProcess, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Process(ProcessId::generate()));

        $form = $this->createForm(RegisterProcessType::class);

        try {
            $processId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerProcess, $loggedUser, &$processId) {
                $registerRequest = $form->getData();

                $response = $registerProcess($registerRequest, $loggedUser());

                $processId = $response->processId;

                $this->addFlash('success', new TranslatableMessage('Proceso creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($processId instanceof ProcessId) {
                    return $this->redirectToRoute('admin_processes_edit', ['processId' => $processId]);
                }

                return $this->redirectToRoute('admin_processes');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/processes/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
