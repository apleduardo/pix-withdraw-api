<?php

declare(strict_types=1);

namespace HyperfTest\Unit;

use App\Service\WithdrawService;
use App\Repository\AccountRepository;
use App\Repository\AccountWithdrawRepository;
use App\Repository\AccountWithdrawPixRepository;
use PHPUnit\Framework\TestCase;
use Mockery;

class WithdrawServiceTest extends TestCase
{
    public function testWithdrawFailsIfAccountNotFound()
    {
        $accountRepo = Mockery::mock(AccountRepository::class);
        $accountRepo->shouldReceive('findByIdForUpdate')->andReturn(null);
        $withdrawRepo = Mockery::mock(AccountWithdrawRepository::class);
        $pixRepo = Mockery::mock(AccountWithdrawPixRepository::class);
        $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('error')->atLeast()->once();
        $service = new WithdrawService($accountRepo, $withdrawRepo, $pixRepo, $logger);
        $result = $service->withdraw('fake-id', [
            'amount' => 100,
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => 'a@a.com'],
        ]);
        $this->assertEquals(['error' => 'Account not found', 'status' => 404], $result);
    }

    public function testWithdrawFailsIfInsufficientBalance()
    {
        $account = new \App\Model\Account([
            'id' => 'id',
            'name' => 'Test',
            'balance' => 50,
        ]);
        $accountRepo = Mockery::mock(AccountRepository::class);
        $accountRepo->shouldReceive('findByIdForUpdate')->andReturn($account);
        $withdraw = new \App\Model\AccountWithdraw([
            'id' => 'withdraw-id',
            'account_id' => 'id',
            'method' => 'PIX',
            'amount' => 100,
            'scheduled' => false,
            'scheduled_for' => null,
            'done' => true,
            'error' => false,
            'error_reason' => null,
        ]);
        $withdrawRepo = Mockery::mock(AccountWithdrawRepository::class);
        $withdrawRepo->shouldReceive('create')->andReturn($withdraw);
        $pixRepo = Mockery::mock(AccountWithdrawPixRepository::class);
        $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('error')->atLeast()->once();
        $service = new WithdrawService($accountRepo, $withdrawRepo, $pixRepo, $logger);
        $result = $service->withdraw('id', [
            'amount' => 100,
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => 'a@a.com'],
        ]);
        $this->assertEquals(['error' => 'Insufficient balance', 'status' => 422], $result);
    }

    public function testWithdrawSuccess()
    {
        $account = new \App\Model\Account([
            'id' => 'id',
            'name' => 'Test',
            'balance' => 150,
        ]);
        $accountRepo = Mockery::mock(AccountRepository::class);
        $accountRepo->shouldReceive('findByIdForUpdate')->andReturn($account);
        $accountRepo->shouldReceive('save')->once()->andReturn(true);
        $withdraw = new \App\Model\AccountWithdraw([
            'id' => 'withdraw-id',
            'account_id' => 'id',
            'method' => 'PIX',
            'amount' => 100,
            'scheduled' => false,
            'scheduled_for' => null,
            'done' => true,
            'error' => false,
            'error_reason' => null,
        ]);
        $withdrawRepo = Mockery::mock(AccountWithdrawRepository::class);
        $withdrawRepo->shouldReceive('create')->once()->andReturn($withdraw);
        $pix = new \App\Model\AccountWithdrawPix([
            'account_withdraw_id' => 'withdraw-id',
            'type' => 'email',
            'key' => 'a@a.com',
        ]);
        $pixRepo = Mockery::mock(AccountWithdrawPixRepository::class);
        $pixRepo->shouldReceive('create')->once()->andReturn($pix);
        $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('info')->atLeast()->once();
        $service = new WithdrawService($accountRepo, $withdrawRepo, $pixRepo, $logger);
        $result = $service->withdraw('id', [
            'amount' => 100,
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => 'a@a.com'],
        ]);
        $this->assertEquals(['success' => true], $result);
        $this->assertEquals(50, $account->balance);
    }
}
