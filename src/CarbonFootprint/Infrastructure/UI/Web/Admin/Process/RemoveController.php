<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Process;

use App\CarbonFootprint\Application\Process\Remove\RemoveProcessRequest;
use App\CarbonFootprint\Application\Process\Remove\RemoveProcessService;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/processes/remove/{processId}', name: 'admin_processes_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetProcessFromIdTrait;

    public function __invoke(
        string $processId,
        ProcessRepository $processRepository,
        RemoveProcessService $removeProcess,
        LoggedUser $loggedUser,
    ): Response {
        $process = $this->getProcessFromId($processId, $processRepository);

        try {
            $removeProcess(new RemoveProcessRequest($process->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el proceso {process}', ['{process}' => $process->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el proceso {process}', ['{process}' => $process->name()]));
        }

        return $this->redirectToRoute('admin_processes');
    }
}
