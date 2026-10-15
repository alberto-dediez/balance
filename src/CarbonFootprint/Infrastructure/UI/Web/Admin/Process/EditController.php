<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Process;

use App\CarbonFootprint\Application\Process\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Process\Update\UpdateProcessRequest;
use App\CarbonFootprint\Application\Process\Update\UpdateProcessService;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateProcessType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/processes/edit/{processId}', name: 'admin_processes_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetProcessFromIdTrait;

    public function __invoke(
        string $processId,
        ProcessRepository $processRepository,
        UpdateProcessService $updateProcess,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $process = $this->getProcessFromId($processId, $processRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $process);

        $emissionFactors = [];
        foreach ($process->emissionFactors() as $processEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromProcessEmissionFactor($processEmissionFactor);
        }

        $processRequest = new UpdateProcessRequest(
            processId: $process->id(),
            name: $process->name(),
            unit: $process->unit(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateProcessType::class, $processRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateProcess, $loggedUser) {
                $processRequest = $form->getData();

                $updateProcess($processRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Proceso actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_processes');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/processes/edit.html.twig', [
            'process' => $process,
            'form' => $form->createView(),
        ]);
    }
}
