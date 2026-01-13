<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

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
        $emailService = Mockery::mock(\App\Service\EmailServiceInterface::class);
        $service = new WithdrawService($accountRepo, $withdrawRepo, $pixRepo, $logger, $emailService);
        $result = $service->requestWithdraw('fake-id', [
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
        $accountRepo->shouldReceive('save')->andReturn(true);
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
        $withdrawRepo->shouldReceive('save')->zeroOrMoreTimes()->andReturn(true);
        $pix = new \App\Model\AccountWithdrawPix([
            'account_withdraw_id' => 'withdraw-id',
            'type' => 'email',
            'key' => 'a@a.com',
        ]);
        $pixRepo = Mockery::mock(AccountWithdrawPixRepository::class);
        $pixRepo->shouldReceive('create')->once()->andReturn($pix);
        $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('error')->atLeast()->once();
        $emailService = Mockery::mock(\App\Service\EmailServiceInterface::class);
        $service = new WithdrawService($accountRepo, $withdrawRepo, $pixRepo, $logger, $emailService);
        $result = $service->requestWithdraw('id', [
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
        $withdrawRepo->shouldReceive('save')->andReturn(true);
        $pix = new \App\Model\AccountWithdrawPix([
            'account_withdraw_id' => 'withdraw-id',
            'type' => 'email',
            'key' => 'a@a.com',
        ]);
        $pixRepo = Mockery::mock(AccountWithdrawPixRepository::class);
        $pixRepo->shouldReceive('create')->once()->andReturn($pix);
        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('info')->atLeast()->once();
        $logger->shouldReceive('error')->zeroOrMoreTimes();
        $emailService = Mockery::mock(\App\Service\EmailServiceInterface::class);
        $emailService->shouldReceive('sendWithdrawNotification')->once();
        $service = new WithdrawService($accountRepo, $withdrawRepo, $pixRepo, $logger, $emailService);
        $result = $service->requestWithdraw('id', [
            'amount' => 100,
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => 'a@a.com'],
        ]);
        $this->assertEquals(['success' => true], $result);
        $this->assertEquals(50, $account->balance);
    }

    public function testWithdrawSendsEmailToPixKey()
    {
        $account = new \App\Model\Account([
            'id' => 'id',
            'name' => 'Test',
            'balance' => 150,
        ]);
        $accountRepo = \Mockery::mock(\App\Repository\AccountRepository::class);
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
        $withdrawRepo = \Mockery::mock(\App\Repository\AccountWithdrawRepository::class);
        $withdrawRepo->shouldReceive('create')->once()->andReturn($withdraw);
        $withdrawRepo->shouldReceive('save')->andReturn(true);
        $pix = new \App\Model\AccountWithdrawPix([
            'account_withdraw_id' => 'withdraw-id',
            'type' => 'email',
            'key' => 'pix@email.com',
        ]);
        $pixRepo = \Mockery::mock(\App\Repository\AccountWithdrawPixRepository::class);
        $pixRepo->shouldReceive('create')->once()->andReturn($pix);
        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('info')->atLeast()->once();
        $logger->shouldReceive('error')->zeroOrMoreTimes();
        $emailService = \Mockery::mock(\App\Service\EmailServiceInterface::class);
        $emailService->shouldReceive('sendWithdrawNotification')
            ->once()
            ->with('pix@email.com', \Mockery::type('array'));
        $service = new \App\Service\WithdrawService($accountRepo, $withdrawRepo, $pixRepo, $logger, $emailService);
        $result = $service->requestWithdraw('id', [
            'amount' => 100,
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => 'pix@email.com'],
        ]);
        $this->assertEquals(['success' => true], $result);
        $this->assertEquals(50, $account->balance);
    }

    public function testRequestWithdrawScheduledDoesNotDebitOrSendEmail()
    {
        $account = new \App\Model\Account([
            'id' => 'id',
            'name' => 'Test',
            'balance' => 200,
        ]);
        $accountRepo = \Mockery::mock(\App\Repository\AccountRepository::class);
        $accountRepo->shouldReceive('findByIdForUpdate')->andReturn($account);
        $accountRepo->shouldNotReceive('save');
        $withdraw = new \App\Model\AccountWithdraw([
            'id' => 'withdraw-id',
            'account_id' => 'id',
            'method' => 'PIX',
            'amount' => 100,
            'scheduled' => true,
            'scheduled_for' => '2026-01-14 10:00:00',
            'done' => false,
            'error' => false,
            'error_reason' => null,
        ]);
        $withdrawRepo = \Mockery::mock(\App\Repository\AccountWithdrawRepository::class);
        $withdrawRepo->shouldReceive('create')->once()->andReturn($withdraw);
        $withdrawRepo->shouldReceive('save')->andReturn(true);
        $pix = new \App\Model\AccountWithdrawPix([
            'account_withdraw_id' => 'withdraw-id',
            'type' => 'email',
            'key' => 'agendado@email.com',
        ]);
        $pixRepo = \Mockery::mock(\App\Repository\AccountWithdrawPixRepository::class);
        $pixRepo->shouldReceive('create')->once()->andReturn($pix);
        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('info');
        $emailService = \Mockery::mock(\App\Service\EmailServiceInterface::class);
        $emailService->shouldNotReceive('sendWithdrawNotification');
        $service = new \App\Service\WithdrawService($accountRepo, $withdrawRepo, $pixRepo, $logger, $emailService);
        $result = $service->requestWithdraw('id', [
            'amount' => 100,
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => 'agendado@email.com'],
            'schedule' => '2026-01-14 10:00:00',
        ]);
        $this->assertEquals(['success' => true], $result);
        $this->assertEquals(200, $account->balance);
    }

    public function testProcessScheduledWithdrawsExecutaSaqueAgendado()
    {
        $account = new \App\Model\Account([
            'id' => 'id',
            'name' => 'Test',
            'balance' => 200,
        ]);
        $withdraw = new \App\Model\AccountWithdraw([
            'id' => 'withdraw-id',
            'account_id' => 'id',
            'method' => 'PIX',
            'amount' => 100,
            'scheduled' => true,
            'scheduled_for' => '2026-01-13 10:00:00',
            'done' => false,
            'error' => false,
            'error_reason' => null,
        ]);
        $pix = new \App\Model\AccountWithdrawPix([
            'account_withdraw_id' => 'withdraw-id',
            'type' => 'email',
            'key' => 'agendado@email.com',
        ]);
        $accountRepo = \Mockery::mock(\App\Repository\AccountRepository::class);
        $accountRepo->shouldReceive('findByIdForUpdate')->andReturn($account);
        $accountRepo->shouldReceive('save')->once()->andReturn(true);
        $withdrawRepo = \Mockery::mock(\App\Repository\AccountWithdrawRepository::class);
        $withdrawRepo->shouldReceive('findScheduledToProcess')->once()->andReturn([$withdraw]);
        $withdrawRepo->shouldReceive('save')->once()->andReturn(true);
        $pixRepo = \Mockery::mock(\App\Repository\AccountWithdrawPixRepository::class);
        $pixRepo->shouldReceive('findByWithdrawId')->once()->andReturn($pix);
        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('info');
        $emailService = \Mockery::mock(\App\Service\EmailServiceInterface::class);
        $emailService->shouldReceive('sendWithdrawNotification')->once();
        $service = new \App\Service\WithdrawService($accountRepo, $withdrawRepo, $pixRepo, $logger, $emailService);
        $service->processScheduledWithdraws();
        $this->assertEquals(100, $account->balance);
        $this->assertTrue($withdraw->done);
    }
}
