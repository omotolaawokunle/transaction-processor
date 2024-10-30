<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Balance;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\WithFaker;
use App\Exceptions\InsufficientFundsException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionService = app(TransactionService::class);
    }

    public function test_process_credit_transaction(): void
    {
        $user = User::factory()->create();
        Balance::factory()->create([
            'user_id' => $user->id,
            'balance' => 100
        ]);

        $transaction = $this->transactionService->processTransaction($user, 50, TransactionType::CREDIT);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $user->id,
            'amount' => 50,
            'type' => TransactionType::CREDIT->value,
            'status' => TransactionStatus::COMPLETED
        ]);

        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'balance' => 150
        ]);
    }

    public function test_process_debit_transaction()
    {
        $user = User::factory()->create();
        Balance::factory()->create([
            'user_id' => $user->id,
            'balance' => 100
        ]);

        $transaction = $this->transactionService->processTransaction($user, 50, TransactionType::DEBIT);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $user->id,
            'amount' => -50,
            'type' => TransactionType::DEBIT->value,
            'status' => TransactionStatus::COMPLETED->value
        ]);

        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'balance' => 50
        ]);
    }

    public function test_insufficient_funds_throws_exception()
    {
        $user = User::factory()->create();
        Balance::factory()->create([
            'user_id' => $user->id,
            'balance' => 100
        ]);

        $this->expectException(InsufficientFundsException::class);
        $this->expectExceptionMessage('Insufficient funds!');

        $this->transactionService->processTransaction($user, 150, TransactionType::DEBIT);
    }

    public function test_creates_balance_if_not_exists()
    {
        $user = User::factory()->create();

        $this->transactionService->processTransaction($user, 100, TransactionType::CREDIT);

        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'balance' => 100
        ]);
    }
}
