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
                'pix.type.in' => 'The pix.type field must be one of the following types: email.',
            ]
        );
        if ($validator->fails()) {
            $this->logger->error('Withdraw validation failed', ['accountId' => $accountId, 'errors' => $validator->errors()->all()]);
            return $response->json(['error' => $validator->errors()->first()])->withStatus(422);
        }
        $result = $this->withdrawService->withdraw($accountId, $data);
        if (isset($result['error'])) {
            $this->logger->error('Withdraw failed', ['accountId' => $accountId, 'error' => $result['error']]);
            return $response->json(['error' => $result['error']])->withStatus($result['status'] ?? 500);
        }
        $this->logger->info('Withdraw succeeded', ['accountId' => $accountId]);
        return $response->json(['success' => true]);
    }
}
