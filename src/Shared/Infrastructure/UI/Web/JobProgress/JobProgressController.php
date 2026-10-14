<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\JobProgress;

use App\Shared\Domain\Model\JobProgress\JobProgressKey;
use App\Shared\Domain\Model\JobProgress\JobProgressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/job-progress/{key}', name: 'job_progress')]
class JobProgressController extends AbstractController
{
    public function __invoke(
        string $key,
        JobProgressRepository $jobProgressRepository,
        TranslatorInterface $translator,
    ): JsonResponse {

        $jobProgress = $jobProgressRepository->findByKey(JobProgressKey::fromString($key));

        if (null === $jobProgress) {
            return $this->json(null);
        }

        return $this->json([
            'current' => $jobProgress->current(),
            'total' => $jobProgress->total(),
            'percentage' => $jobProgress->percentage(),
            'currentStep' => $translator->trans($jobProgress->currentStep()),
            'status' => $jobProgress->status()->value,
        ]);
    }
}
