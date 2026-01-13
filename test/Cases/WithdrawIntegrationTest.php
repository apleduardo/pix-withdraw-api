<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use Hyperf\Testing\TestCase;
use Hyperf\DbConnection\Db;

class WithdrawIntegrationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Desabilita checagem de foreign key antes de truncar
        Db::statement('SET FOREIGN_KEY_CHECKS=0;');
        Db::table('account_withdraw_pix')->truncate();
        Db::table('account_withdraw')->truncate();
        Db::table('account')->truncate();
        Db::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function testWithdrawSuccess()
    {
        $account = Account::create([
            'id' => '11111111-1111-1111-1111-111111111111',
            'name' => 'Test',
            'balance' => 200.00,
        ]);
        $payload = [
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => 'a@a.com'],
            'amount' => 100.00,
            'schedule' => null
        ];
        $response = $this->post("/account/{$account->id}/balance/withdraw", $payload);
        $response->assertOk()->assertJson(['success' => true]);
        $account->refresh();
        $this->assertEquals(100.00, $account->balance);
        $this->assertDatabaseHas('account_withdraw', ['account_id' => $account->id, 'amount' => 100.00]);
        $this->assertDatabaseHas('account_withdraw_pix', ['type' => 'email', 'key' => 'a@a.com']);
    }

    public function testWithdrawPixTypeInvalid()
    {
        $account = Account::create([
            'id' => '22222222-2222-2222-2222-222222222222',
            'name' => 'Test2',
            'balance' => 200.00,
        ]);
        $payload = [
            'method' => 'PIX',
            'pix' => ['type' => 'cpf', 'key' => '12345678900'],
            'amount' => 100.00,
            'schedule' => null
        ];
        $response = $this->post("/account/{$account->id}/balance/withdraw", $payload);
        $response->assertStatus(422)->assertJsonFragment([
            'error' => 'The pix.type field must be one of the following types: email.'
        ]);
    }
}
