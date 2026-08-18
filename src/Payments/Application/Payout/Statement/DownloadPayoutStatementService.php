<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Statement;

use App\Payments\Application\Payout\View\ViewPayoutRequest;
use App\Payments\Application\Payout\View\ViewPayoutService;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payout\PayoutStatementLinesProvider;
use App\Payments\Domain\Model\Payout\PayoutStatementPdfGenerator;
use App\Security\Domain\Model\User\User;

final class DownloadPayoutStatementService
{
    public function __construct(
        private ViewPayoutService $viewPayout,
        private PayoutStatementLinesProvider $linesProvider,
        private PayoutStatementPdfGenerator $pdfGenerator,
    ) {}

    public function __invoke(DownloadPayoutStatementRequest $request, User $user): DownloadPayoutStatementResponse
    {
        $view = $this->viewPayout->__invoke(new ViewPayoutRequest($request->payoutId), $user);

        if (!$view->payee instanceof Payee) {
            throw new \InvalidArgumentException('Payee not found');
        }

        $path = $this->pdfGenerator->generate(
            $view->payout,
            $view->payee,
            $this->linesProvider->linesOf($view->payout->id()),
        );

        return new DownloadPayoutStatementResponse(
            path: $path,
            filename: sprintf('liquidacion-%s.pdf', str_replace('/', '-', (string) $view->payout->number())),
        );
    }
}
