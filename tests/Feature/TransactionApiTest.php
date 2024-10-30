<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Balance;
use App\Models\Transaction;
use Laravel\Sanctum\Sanctum;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;
    private ?User $user;
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_create_credit_transaction()
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => 100,
            'type' => TransactionType::CREDIT->value
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'amount',
                    'type',
                    'status',
                    'reference'
                ]
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 100,
            'type' => TransactionType::CREDIT->value,
            'status' => TransactionStatus::COMPLETED->value
        ]);
    }

    public function test_create_debit_transaction()
    {
        Balance::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 200
        ]);

        $response = $this->postJson('/api/transactions', [
            'amount' => 100,
            'type' => TransactionType::DEBIT->value
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'amount',
                    'type',
                    'status',
                    'reference'
                ]
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => -100,
            'type' => TransactionType::DEBIT->value,
            'status' => TransactionStatus::COMPLETED->value
        ]);
    }

    public function test_insufficient_funds_returns_error()
    {
        Balance::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 50
        ]);

        $response = $this->postJson('/api/transactions', [
            'amount' => 100,
            'type' => TransactionType::DEBIT->value
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 0,
                'error' => 'Insufficient funds!'
            ]);
    }

    public function test_get_balance()
    {
        Balance::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 150
        ]);

        $response = $this->getJson('/api/balance');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data' => [
                    'balance' => 150
                ]
            ]);
    }

    public function test_invalid_transaction_type_returns_error()
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => 100,
            'type' => 'invalid_type'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_negative_amount_returns_error()
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => -100,
            'type' => TransactionType::CREDIT->value
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_concurrent_transactions_maintain_consistency()
    {
        Balance::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 100
        ]);

        $responses = [];

        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->postJson('/api/transactions', [
                'amount' => 20,
                'type' => TransactionType::DEBIT->value
            ]);
        }

        // Verify final balance is correct
        $finalBalance = Balance::where('user_id', $this->user->id)->first()->balance;
        $this->assertEquals(0, $finalBalance);

        // Verify all transactions were processed
        $transactionCount = Transaction::where('user_id', $this->user->id)
            ->where('type', TransactionType::DEBIT->value)
            ->where('status', TransactionStatus::COMPLETED->value)
            ->count();
        $this->assertEquals(5, $transactionCount);
    }
}
