<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\CRUD;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatableMessage;

trait BulkTrait
{
    /**
     * @param FormInterface<mixed> $bulkForm
     */
    public function bulkActions(Request $request, FormInterface $bulkForm, User $user): bool
    {
        $bulkForm->handleRequest($request);

        if ($bulkForm->isSubmitted() && $bulkForm->isValid()) {
            $bulkRequest = $bulkForm->getData();

            $commands = $bulkRequest->commands();

            if (0 === count($commands)) {
                $this->addFlash('danger', new TranslatableMessage('Debes seleccionar algún elemento sobre el que poder realizar la acción'));

                return true;
            }

            foreach ($commands as $command) {
                try {
                    CommandBusDispatcher::instance()
                        ->dispatch($command, $user)
                    ;

                    $this->addFlash('success', $bulkRequest->action->success($command));
                } catch (\Exception $e) {
                    $this->addFlash('warning', $e->getMessage() . $e->getTraceAsString());
                    $this->addFlash('danger', $bulkRequest->action->failure($command));
                }
            }

            return true;
        }

        return false;
    }
}
