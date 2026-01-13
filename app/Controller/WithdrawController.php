<?php

namespace App\Controller;

use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use App\Service\WithdrawService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Di\Annotation\Inject;
use Hyperf\DbConnection\Db;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\Str;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @Controller()
 */
class WithdrawController extends AbstractController
{
    #[Inject]
    protected ValidatorFactoryInterface $validatorFactory;

    #[Inject]
    protected LoggerInterface $logger;

    #[Inject]
    protected WithdrawService $withdrawService;

    /**
     * @PostMapping(path="/account/{accountId}/balance/withdraw")
     */
    public function withdraw(string $accountId, RequestInterface $request, ResponseInterface $response)
    {
        $data = $request->all();
        $this->logger->info('Withdraw request received', ['accountId' => $accountId, 'data' => $data]);
        $validator = $this->validatorFactory->make(
            $data,
            [
                'method' => 'required|in:PIX',
                'pix.type' => 'required|in:email',
                'pix.key' => 'required|email',
                'amount' => 'required|numeric|min:0.01',
                'schedule' => 'nullable|date|after_or_equal:now',
            ],
            [
                'method.required' => 'The method field is required.',
                'method.in' => 'The method field must be PIX.',
                'pix.type.required' => 'The pix.type field is required.',
                'pix.type.in' => 'The pix.type field must be one of the following types: email.',
                'pix.key.required' => 'The pix.key field is required.',
                'pix.key.email' => 'The pix.key field must be a valid email address.',
                'amount.required' => 'The amount field is required.',
                'amount.numeric' => 'The amount field must be a number.',
                'amount.min' => 'The amount must be at least 0.01.',
            ]
        );
        if ($validator->fails()) {
            // Always return the first error for the first failed field in the order of rules
            $errors = $validator->errors()->all();
            $errorMsg = $errors[0] ?? 'Validation error.';
            $this->logger->error('Withdraw validation failed', ['accountId' => $accountId, 'errors' => $errors]);
            return $response->json(['error' => $errorMsg])->withStatus(422);
        }
        $result = $this->withdrawService->requestWithdraw($accountId, $data);
        if (isset($result['error'])) {
            $this->logger->error('Withdraw failed', ['accountId' => $accountId, 'error' => $result['error']]);
            return $response->json(['error' => $result['error']])->withStatus($result['status'] ?? 500);
        }
        $this->logger->info('Withdraw succeeded', ['accountId' => $accountId]);
        return $response->json(['success' => true]);
    }
}
